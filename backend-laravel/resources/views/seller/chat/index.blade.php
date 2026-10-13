@extends('layouts.seller')

@section('content')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('sellerChat', (config = {}) => ({
        conversations: [],
        messages: [],
        activeUser: null,
        newMessage: '',
        autoOpenUserId: config.autoOpenUserId || '',
        autoOpenUserName: config.autoOpenUserName || 'Customer',
        currentUserId: config.currentUserId || null,
        pollInterval: null,
        isLoadingMessages: false,
        isLoadingConversations: false,

        // ─── Quick Replies / Pre-made Text State ───
        quickReplies: [],
        isLoadingQuickReplies: false,
        showQuickRepliesModal: false,
        showAddEditModal: false,
        showQuickReplyPicker: false,
        quickReplyFilter: 'All', // 'All', 'Order Updates', 'Customer Inquiries', 'Sizing & Fitting'
        quickReplyToast: '',
        quickReplyToastTimeout: null,
        editingReplyIndex: -1,
        quickReplyForm: {
            id: '',
            title: '',
            category: 'Order Updates',
            text: ''
        },

        init() {
            this.loadConversations();
            this.loadQuickReplies();
            setInterval(() => this.loadConversations(), 10000);

            // Auto-open conversation if redirected from Customer Directory
            if (this.autoOpenUserId) {
                // Wait for conversations to load then find and open the user
                setTimeout(() => {
                    const existing = this.conversations.find(c => c.otherUser && String(c.otherUser.id) === String(this.autoOpenUserId));
                    if (existing) {
                        this.selectUser(existing.otherUser);
                    } else {
                        // No prior conversation — open thread directly so seller can start one
                        this.selectUser({ id: this.autoOpenUserId, name: this.autoOpenUserName });
                    }
                }, 800);
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        },

        loadConversations() {
            if (this.isLoadingConversations) return;
            this.isLoadingConversations = true;
            fetch('/api/chat/conversations')
                .then(res => res.json())
                .then(data => { this.conversations = Array.isArray(data) ? data : []; })
                .catch(() => {})
                .finally(() => { this.isLoadingConversations = false; });
        },

        selectUser(user) {
            this.activeUser = user;
            this.showQuickReplyPicker = false;
            this.loadMessages();
            this.startPolling();
        },

        loadMessages() {
            if (!this.activeUser || this.isLoadingMessages) return;
            this.isLoadingMessages = true;
            fetch('/api/chat/conversation/' + this.activeUser.id)
                .then(res => res.json())
                .then(data => {
                    this.messages = Array.isArray(data) ? data : [];
                    this.scrollToBottom();
                })
                .catch(() => {})
                .finally(() => { this.isLoadingMessages = false; });
        },

        sendMessage() {
            if (!this.newMessage.trim() || !this.activeUser) return;
            const content = this.newMessage;
            this.newMessage = '';
            this.showQuickReplyPicker = false;

            const tempId = Math.random().toString();
            this.messages.push({
                id: tempId,
                senderId: this.currentUserId,
                receiverId: this.activeUser.id,
                content,
                createdAt: new Date().toISOString()
            });
            this.scrollToBottom();

            fetch('/api/chat/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ receiverId: this.activeUser.id, content })
            })
            .then(res => res.json())
            .then(data => {
                const idx = this.messages.findIndex(m => m.id === tempId);
                if (idx !== -1) this.messages[idx] = data;
                this.loadConversations();
            })
            .catch(() => {});
        },

        deleteConversation(otherUserId) {
            if (!confirm('Delete this conversation? This cannot be undone.')) return;
            fetch('/api/chat/conversation/' + otherUserId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrfToken() }
            })
            .then(res => res.json())
            .then(() => {
                if (this.activeUser && this.activeUser.id === otherUserId) {
                    this.activeUser = null;
                    this.messages = [];
                    this.stopPolling();
                }
                this.loadConversations();
            })
            .catch(() => {});
        },

        startPolling() {
            this.stopPolling();
            this.pollInterval = setInterval(() => this.loadMessages(), 3000);
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.threadBox;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '';
            try {
                const d = new Date(dateStr);
                if (isNaN(d.getTime())) return '';
                const now = new Date();
                const isToday = d.toDateString() === now.toDateString();
                const timeStr = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', hour12: true });
                if (isToday) {
                    return `Today, ${timeStr}`;
                }
                const isThisYear = d.getFullYear() === now.getFullYear();
                const datePart = d.toLocaleDateString([], { month: 'short', day: 'numeric', ...(isThisYear ? {} : { year: 'numeric' }) });
                return `${datePart} • ${timeStr}`;
            } catch { return ''; }
        },

        formatChatMessage(rawText) {
            if (!rawText) return '';
            let text = String(rawText);
            
            // Strip internal metadata comments
            text = text.replace(new RegExp('<!--[\\s\\S]*?-->', 'g'), '');
            
            // Escape HTML special characters for XSS protection
            text = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            // In seller side: Product image inside link [![alt](imgUrl)](linkUrl) is NOT CLICKABLE.
            // Customers click it to direct to product/order, but sellers view it as a non-clickable preview.
            text = text.replace(/\[!\[([^\]]*)\]\(([^)]+)\)\]\(([^)]+)\)/g, function(match, alt, imgUrl, linkUrl) {
                const safeImg = imgUrl.replace(/"/g, '&quot;');
                const safeAlt = alt.replace(/"/g, '&quot;');
                return '<div class="block my-2 pointer-events-none select-none"><img src="' + safeImg + '" alt="' + safeAlt + '" class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-xl border border-gray-200 shadow-xs cursor-default" onerror="this.style.display=\'none\'"></div>';
            });

            // Parse standalone markdown image: ![alt](imgUrl)
            text = text.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, function(match, alt, imgUrl) {
                const safeImg = imgUrl.replace(/"/g, '&quot;');
                const safeAlt = alt.replace(/"/g, '&quot;');
                return '<div class="block my-2 pointer-events-none select-none"><img src="' + safeImg + '" alt="' + safeAlt + '" class="w-20 h-20 sm:w-24 sm:h-24 object-cover rounded-xl border border-gray-200 shadow-xs cursor-default" onerror="this.style.display=\'none\'"></div>';
            });

            // In seller side: Parse markdown link [Text](url).
            // Any product links (/products/...) or order links (/orders/...) are NOT CLICKABLE for the seller.
            text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, function(match, label, linkUrl) {
                const safeLink = linkUrl.replace(/"/g, '&quot;');
                const isProductOrOrderLink = safeLink.includes('/products/') || safeLink.includes('/orders/');
                if (isProductOrOrderLink) {
                    return '<span class="opacity-90 font-bold inline-flex items-center gap-1 cursor-default pointer-events-none">' + label + '</span>';
                }
                return '<a href="' + safeLink + '" class="text-[#C0422A] underline font-bold transition-colors inline-flex items-center gap-1 hover:text-[#A33520] cursor-pointer" target="_blank">' + label + ' &rarr;</a>';
            });

            // Bold **text**
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            // Italic *text*
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

            // Newlines
            text = text.replace(/\n/g, '<br>');

            return text;
        },

        formatPreviewText(raw) {
            if (!raw) return '';
            return String(raw)
                .replace(new RegExp('<!--[\\s\\S]*?-->', 'g'), '')
                .replace(/\[!\[.*?\]\(.*?\)\]\(.*?\)/g, '[Image]')
                .replace(/!\[.*?\]\(.*?\)/g, '[Image]')
                .replace(/\[(.*?)\]\(.*?\)/g, '$1')
                .replace(/[*_#`]/g, '')
                .trim();
        },

        // ─── Quick Replies / Pre-made Text Methods ───
        loadQuickReplies() {
            this.isLoadingQuickReplies = true;
            fetch('/api/chat/quick-replies')
                .then(res => res.json())
                .then(data => {
                    this.quickReplies = Array.isArray(data) ? data : [];
                })
                .catch(() => {})
                .finally(() => { this.isLoadingQuickReplies = false; });
        },

        showToast(msg) {
            this.quickReplyToast = msg;
            if (this.quickReplyToastTimeout) clearTimeout(this.quickReplyToastTimeout);
            this.quickReplyToastTimeout = setTimeout(() => { this.quickReplyToast = ''; }, 3000);
        },

        openQuickRepliesSettings() {
            this.showQuickRepliesModal = true;
            this.showQuickReplyPicker = false;
            this.showAddEditModal = false;
            if (!this.quickReplies || this.quickReplies.length === 0) {
                this.loadQuickReplies();
            }
        },

        closeQuickRepliesSettings() {
            this.showQuickRepliesModal = false;
            this.showAddEditModal = false;
        },

        openAddReplyModal() {
            this.editingReplyIndex = -1;
            this.quickReplyForm = {
                id: 'qr-' + Date.now(),
                title: '',
                category: this.quickReplyFilter !== 'All' ? this.quickReplyFilter : 'Order Updates',
                text: ''
            };
            this.showAddEditModal = true;
        },

        openEditReplyModal(reply, index) {
            this.editingReplyIndex = index;
            this.quickReplyForm = {
                id: reply.id || ('qr-' + Date.now()),
                title: reply.title || '',
                category: reply.category || 'Order Updates',
                text: reply.text || ''
            };
            this.showAddEditModal = true;
        },

        saveQuickReplyItem() {
            const title = (this.quickReplyForm.title || '').trim();
            const text = (this.quickReplyForm.text || '').trim();
            const category = (this.quickReplyForm.category || '').trim() || 'General';

            if (!title) {
                alert('Please enter a template title/shortcut.');
                return;
            }
            if (!text) {
                alert('Please enter the response message text.');
                return;
            }

            const item = {
                id: this.quickReplyForm.id || ('qr-' + Date.now()),
                title,
                category,
                text
            };

            if (this.editingReplyIndex >= 0 && this.editingReplyIndex < this.quickReplies.length) {
                this.quickReplies[this.editingReplyIndex] = item;
            } else {
                this.quickReplies.unshift(item);
            }

            this.showAddEditModal = false;
            this.saveQuickRepliesToServer('Template saved successfully!');
        },

        deleteQuickReplyItem(index) {
            if (!confirm('Are you sure you want to delete this pre-made response?')) return;
            this.quickReplies.splice(index, 1);
            this.saveQuickRepliesToServer('Template deleted.');
        },

        resetQuickRepliesToDefault() {
            if (!confirm('Reset all pre-made responses to the recommended artisan defaults? Custom changes will be replaced.')) return;
            fetch('/api/chat/quick-replies/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken()
                }
            })
            .then(res => res.json())
            .then(data => {
                this.quickReplies = data.replies || [];
                this.showToast('Pre-made responses reset to defaults!');
            })
            .catch(() => alert('Failed to reset templates.'));
        },

        saveQuickRepliesToServer(successMsg) {
            fetch('/api/chat/quick-replies', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ replies: this.quickReplies })
            })
            .then(res => res.json())
            .then(data => {
                if (data.replies) this.quickReplies = data.replies;
                this.showToast(successMsg || 'Settings updated!');
            })
            .catch(() => alert('Failed to save templates to server.'));
        },

        insertQuickReply(text) {
            if (!text) return;
            if (this.newMessage && this.newMessage.trim()) {
                this.newMessage = this.newMessage + ' ' + text;
            } else {
                this.newMessage = text;
            }
            this.showQuickReplyPicker = false;
            this.showQuickRepliesModal = false;
            this.$nextTick(() => {
                const inputEl = this.$refs.replyInput;
                if (inputEl) {
                    inputEl.focus();
                    inputEl.setSelectionRange(inputEl.value.length, inputEl.value.length);
                }
            });
        },

        filteredQuickReplies() {
            if (!this.quickReplies) return [];
            if (this.quickReplyFilter === 'All') return this.quickReplies;
            return this.quickReplies.filter(r => r.category === this.quickReplyFilter);
        }
    }));
});
</script>

<div
    x-data="sellerChat({ autoOpenUserId: '{{ $autoOpenUserId ?? '' }}', autoOpenUserName: '{{ addslashes($autoOpenUserName ?? 'Customer') }}', currentUserId: '{{ Auth::id() ?? '' }}' })"
    class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex h-[calc(100vh-140px)] sm:h-[calc(100vh-180px)] min-h-112.5 relative"
>
    <!-- Left Sidebar: Conversations -->
    <div 
        class="w-full md:w-80 border-r border-gray-100 flex flex-col bg-white h-full shrink-0"
        :class="activeUser ? 'hidden md:flex' : 'flex'"
    >
        <div class="p-4 sm:p-6 border-b border-gray-100 shrink-0 flex items-center justify-between">
            <div>
                <h2 class="font-serif text-base sm:text-lg font-bold text-black uppercase">Inbox</h2>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Customer Inquiries</p>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto no-scrollbar p-3 sm:p-4 space-y-2 bg-gray-50/30">
            <template x-for="conv in conversations" :key="conv.otherUser.id">
                <div
                    class="flex items-center justify-between p-3.5 sm:p-4 bg-white rounded-2xl border transition-all cursor-pointer group"
                    :class="activeUser && activeUser.id === conv.otherUser.id ? 'border-black shadow-md' : 'border-gray-100 hover:border-black/20'"
                    @click="selectUser(conv.otherUser)"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-600 shrink-0 uppercase border border-gray-200 text-xs sm:text-sm overflow-hidden">
                            <template x-if="conv.otherUser && conv.otherUser.profileImage">
                                <img :src="conv.otherUser.profileImage" class="w-full h-full object-cover" x-on:error="$el.style.display='none'">
                            </template>
                            <span x-show="!conv.otherUser || !conv.otherUser.profileImage" x-text="((conv.otherUser && conv.otherUser.name) || 'C').charAt(0)"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-black truncate" x-text="conv.otherUser && conv.otherUser.name ? conv.otherUser.name : 'Customer'"></div>
                            <p class="text-[10px] text-gray-400 truncate leading-relaxed mt-0.5" x-text="formatPreviewText(typeof conv.lastMessage === 'object' ? (conv.lastMessage.content || conv.lastMessage.body || '') : (conv.lastMessage || ''))"></p>
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-2 flex flex-col items-end gap-1.5">
                        <div class="text-[9px] font-medium text-gray-400" x-text="formatDateTime(conv.timestamp)"></div>
                        <div class="flex items-center gap-1">
                            <template x-if="conv.unreadCount > 0">
                                <span class="inline-block min-w-4 h-4 px-1 bg-red-500 text-white text-[8px] font-bold rounded-full text-center"
                                      x-text="conv.unreadCount"></span>
                            </template>
                            <button
                                @click.stop="deleteConversation(conv.otherUser.id)"
                                class="opacity-0 group-hover:opacity-100 p-1 hover:text-red-500 rounded transition-all"
                                title="Delete Conversation"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <template x-if="conversations.length === 0">
                <div class="py-20 text-center">
                    <p class="text-xs text-gray-400 italic">No messages received yet.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Right Panel: Thread -->
    <div 
        class="w-full flex-1 flex flex-col bg-white h-full min-w-0"
        :class="activeUser ? 'flex' : 'hidden md:flex'"
    >
        <template x-if="activeUser">
            <div class="flex flex-col h-full min-h-0">
                <!-- Thread Header -->
                <div class="px-4 sm:px-8 py-3.5 sm:py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                    <div class="flex items-center gap-3">
                        <button @click="activeUser = null" class="md:hidden flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 text-gray-600 hover:text-black shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </button>
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-600 shrink-0 uppercase border border-gray-200 text-xs sm:text-sm overflow-hidden">
                            <template x-if="activeUser && activeUser.profileImage">
                                <img :src="activeUser.profileImage" class="w-full h-full object-cover" x-on:error="$el.style.display='none'">
                            </template>
                            <span x-show="!activeUser || !activeUser.profileImage" x-text="((activeUser && activeUser.name) || 'C').charAt(0)"></span>
                        </div>
                        <div>
                            <h3 class="font-serif text-sm font-bold text-black" x-text="activeUser.name"></h3>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Customer Chat Thread</p>
                        </div>
                    </div>

                    <!-- Header Action: Pre-made Responses Settings Button -->
                    <button
                        type="button"
                        @click="openQuickRepliesSettings()"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-white text-xs font-bold text-gray-700 hover:text-black hover:border-black transition-all shadow-2xs hover:shadow-xs cursor-pointer shrink-0"
                        title="Manage pre-made responses for orders and inquiries"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>Manage</span>
                    </button>
                </div>

                <!-- Messages Stream -->
                <div x-ref="threadBox" class="flex-1 overflow-y-auto no-scrollbar p-4 sm:p-8 space-y-4 bg-gray-50/10">
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex flex-col max-w-full min-w-0" :class="msg.senderId === currentUserId ? 'items-end' : 'items-start'">
                            <div class="max-w-[85%] sm:max-w-[70%] px-4 sm:px-5 py-3 rounded-2xl text-xs leading-relaxed break-words [word-break:break-word] overflow-hidden"
                                 :class="msg.senderId === currentUserId
                                     ? 'bg-[#C0422A] text-white rounded-tr-none'
                                     : 'bg-gray-100 text-gray-800 rounded-tl-none border border-gray-200/50'"
                                 x-html="formatChatMessage(msg.content || msg.body || '')"></div>
                            <span class="text-[9px] font-medium text-gray-400 mt-1" x-text="formatDateTime(msg.createdAt)"></span>
                        </div>
                    </template>
                    <template x-if="messages.length === 0">
                        <div class="py-20 text-center">
                            <p class="text-xs text-gray-400 italic">No messages in thread.</p>
                        </div>
                    </template>
                </div>

                <!-- Quick Reply Toolbar & Fast Shortcut Chips -->
                <div class="px-3.5 sm:px-6 pt-2.5 pb-2 border-t border-gray-100 bg-[#FAF7F2]/60 flex items-center gap-2 overflow-x-auto no-scrollbar shrink-0">
                    <button
                        type="button"
                        @click="showQuickReplyPicker = !showQuickReplyPicker"
                        class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-[#C0422A]/10 text-[#C0422A] text-[11px] font-bold hover:bg-[#C0422A]/20 transition-all shrink-0 cursor-pointer"
                        title="Choose from all pre-made responses"
                    >
                        <x-lucide name="sparkles" class="w-3.5 h-3.5 text-amber-500 inline" />
                        <span>Quick Replies</span>
                        <svg class="w-3 h-3 transition-transform" :class="showQuickReplyPicker ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <!-- Top Shortcut Chips for Fast 1-Click Insertion -->
                    <template x-for="chip in (quickReplies || []).slice(0, 4)" :key="chip.id">
                        <button
                            type="button"
                            @click="insertQuickReply(chip.text)"
                            class="px-2.5 py-1 rounded-lg bg-white border border-gray-200 text-gray-700 hover:text-black hover:border-gray-400 text-[11px] font-semibold transition-all shrink-0 whitespace-nowrap cursor-pointer shadow-2xs hover:shadow-xs"
                            :title="chip.text"
                        >
                            <span x-text="chip.title"></span>
                        </button>
                    </template>
                </div>

                <!-- Quick Reply Picker Slide-Down Popover -->
                <div
                    x-show="showQuickReplyPicker"
                    x-cloak
                    @click.away="showQuickReplyPicker = false"
                    class="border-t border-gray-100 bg-white p-3 sm:p-4 max-h-60 overflow-y-auto no-scrollbar shadow-inner space-y-2"
                >
                    <div class="flex items-center justify-between pb-1.5 border-b border-gray-100">
                        <span class="text-[10px] font-black uppercase tracking-wider text-gray-400">Choose a Pre-made Response</span>
                        <button type="button" @click="openQuickRepliesSettings()" class="text-[10px] font-bold text-[#C0422A] hover:underline cursor-pointer">
                            <x-lucide name="pencil" class="w-3.5 h-3.5 inline mr-1" /> Manage All Templates
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <template x-for="r in quickReplies" :key="r.id">
                            <div
                                @click="insertQuickReply(r.text)"
                                class="p-2.5 rounded-xl border border-gray-100 hover:border-[#C0422A]/40 hover:bg-[#FAF7F2] transition-all cursor-pointer group text-left"
                            >
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-xs font-bold text-gray-900 group-hover:text-[#C0422A] truncate" x-text="r.title"></span>
                                    <span class="text-[9px] px-1.5 py-0.5 rounded-md font-semibold shrink-0"
                                          :class="r.category === 'Order Updates' ? 'bg-blue-50 text-blue-700' : (r.category === 'Sizing & Fitting' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700')"
                                          x-text="r.category"></span>
                                </div>
                                <p class="text-[11px] text-gray-500 line-clamp-2 leading-tight" x-text="r.text"></p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Reply Input -->
                <div class="p-3.5 sm:p-6 border-t border-gray-100 flex items-center gap-2 sm:gap-4 bg-white shrink-0">
                    <input
                        type="text"
                        x-ref="replyInput"
                        x-model="newMessage"
                        @keyup.enter="sendMessage()"
                        placeholder="Type a response or select a quick reply above..."
                        class="flex-1 bg-gray-50 border border-gray-200 rounded-xl sm:rounded-2xl px-4 sm:px-6 py-3 sm:py-4 text-xs focus:outline-none focus:border-black/20"
                    >
                    <button
                        @click="sendMessage()"
                        class="px-4 sm:px-6 py-3 sm:py-4 bg-[#C0422A] text-white rounded-xl sm:rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-black transition-colors shrink-0 cursor-pointer"
                    >
                        Send
                    </button>
                </div>
            </div>
        </template>

        <template x-if="!activeUser">
            <div class="flex-1 flex flex-col items-center justify-center p-8">
                <svg class="w-12 h-12 text-gray-300 mb-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <h3 class="font-serif text-base font-bold text-black uppercase">No Conversation Selected</h3>
                <p class="text-xs text-gray-400 mt-2 text-center">Select a customer inquiry to start messaging.</p>
                <button
                    type="button"
                    @click="openQuickRepliesSettings()"
                    class="mt-4 px-4 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:text-black hover:border-black transition-all flex items-center gap-1.5 cursor-pointer bg-white"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Manage Pre-made Responses</span>
                </button>
            </div>
        </template>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- PRE-MADE TEXT / QUICK REPLIES SETTINGS MODAL                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div
        x-show="showQuickRepliesModal"
        x-cloak
        style="display: none; z-index: 100050 !important;"
        class="fixed inset-0 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        @keydown.escape.window="closeQuickRepliesSettings()"
    >
        <!-- Modal Backdrop -->
        <div class="fixed inset-0" @click="closeQuickRepliesSettings()"></div>

        <!-- Modal Panel -->
        <div class="relative bg-white rounded-3xl shadow-2xl border border-gray-100 max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden z-10" @click.stop>
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between bg-white shrink-0">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-[#C0422A]"></span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Artisan Message Settings</span>
                    </div>
                    <h3 class="font-serif text-xl font-bold text-gray-900">Pre-made Responses & Templates</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Manage canned replies for customer orders, sizing inquiries, and product questions.</p>
                </div>
                <button
                    type="button"
                    @click="closeQuickRepliesSettings()"
                    class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Toolbar: Category Filter Tabs & Add Button -->
            <div class="px-6 py-3.5 bg-gray-50/70 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 shrink-0">
                <!-- Filter Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                    <template x-for="cat in ['All', 'Order Updates', 'Customer Inquiries', 'Sizing & Fitting']" :key="cat">
                        <button
                            type="button"
                            @click="quickReplyFilter = cat"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer whitespace-nowrap"
                            :class="quickReplyFilter === cat ? 'bg-black text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-600 hover:border-gray-300'"
                            x-text="cat"
                        ></button>
                    </template>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 ml-auto">
                    <button
                        type="button"
                        @click="resetQuickRepliesToDefault()"
                        class="px-3 py-1.5 rounded-xl border border-gray-200 bg-white text-gray-600 hover:text-black text-xs font-semibold hover:border-gray-300 transition-all cursor-pointer"
                        title="Reset to recommended defaults"
                    >
                        <x-lucide name="rotate-ccw" class="w-3.5 h-3.5 inline mr-1" /> Defaults
                    </button>
                    <button
                        type="button"
                        @click="openAddReplyModal()"
                        class="px-3.5 py-1.5 rounded-xl bg-[#C0422A] hover:bg-black text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1 cursor-pointer"
                    >
                        <span>+ Add Template</span>
                    </button>
                </div>
            </div>

            <!-- Templates List -->
            <div class="p-6 overflow-y-auto flex-1 space-y-3 bg-[#FAF7F2]/30">
                <template x-for="(reply, idx) in filteredQuickReplies()" :key="reply.id || idx">
                    <div class="bg-white rounded-2xl p-4 border border-gray-100 hover:border-gray-300 transition-all shadow-2xs group flex flex-col gap-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-black text-gray-900" x-text="reply.title"></span>
                                    <span class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider"
                                          :class="reply.category === 'Order Updates' ? 'bg-blue-100 text-blue-800' : (reply.category === 'Sizing & Fitting' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')"
                                          x-text="reply.category"></span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <template x-if="activeUser">
                                    <button
                                        type="button"
                                        @click="insertQuickReply(reply.text)"
                                        class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 hover:bg-emerald-100 text-[11px] font-bold transition-all cursor-pointer"
                                        title="Insert into chat reply"
                                    >
                                        Use Now
                                    </button>
                                </template>
                                <button
                                    type="button"
                                    @click="openEditReplyModal(reply, quickReplies.indexOf(reply))"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-black hover:bg-gray-100 transition-all cursor-pointer"
                                    title="Edit"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </button>
                                <button
                                    type="button"
                                    @click="deleteQuickReplyItem(quickReplies.indexOf(reply))"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all cursor-pointer"
                                    title="Delete"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed bg-gray-50/70 p-3 rounded-xl border border-gray-100/80 font-mono text-[11px] whitespace-pre-line" x-text="reply.text"></p>
                    </div>
                </template>

                <template x-if="filteredQuickReplies().length === 0">
                    <div class="py-16 text-center">
                        <p class="text-xs text-gray-400 italic mb-2">No templates found in this category.</p>
                        <button type="button" @click="openAddReplyModal()" class="text-xs font-bold text-[#C0422A] hover:underline cursor-pointer">
                            + Create a new response
                        </button>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-white border-t border-gray-100 flex items-center justify-between text-xs text-gray-400 shrink-0">
                <span x-text="(quickReplies || []).length + ' total templates configured'"></span>
                <button
                    type="button"
                    @click="closeQuickRepliesSettings()"
                    class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold transition-all cursor-pointer"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- ADD / EDIT TEMPLATE SUBMODAL                                        -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div
        x-show="showAddEditModal"
        x-cloak
        style="display: none; z-index: 100060 !important;"
        class="fixed inset-0 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs"
        @keydown.escape.window="showAddEditModal = false"
    >
        <div class="fixed inset-0" @click="showAddEditModal = false"></div>

        <div class="relative bg-white rounded-3xl shadow-2xl border border-gray-100 max-w-lg w-full p-6 space-y-4 z-10" @click.stop>
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h4 class="font-serif text-base font-bold text-gray-900" x-text="editingReplyIndex >= 0 ? 'Edit Pre-made Response' : 'Create New Pre-made Response'"></h4>
                <button type="button" @click="showAddEditModal = false" class="text-gray-400 hover:text-black">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-gray-800 mb-1">Title / Shortcut Label <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        x-model="quickReplyForm.title"
                        placeholder="e.g., Fabric Specifications, Tailoring Delay, Fitting Guide..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:border-black/30 focus:bg-white transition-all"
                    >
                </div>

                <div>
                    <label class="block font-bold text-gray-800 mb-1">Category <span class="text-red-500">*</span></label>
                    <select
                        x-model="quickReplyForm.category"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs focus:outline-none focus:border-black/30 focus:bg-white transition-all cursor-pointer"
                    >
                        <option value="Order Updates">Order Updates & Fulfillment</option>
                        <option value="Customer Inquiries">Customer Inquiries & Stock</option>
                        <option value="Sizing & Fitting">Sizing & Measurements</option>
                        <option value="General">Heritage Shop General</option>
                    </select>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="font-bold text-gray-800">Response Text <span class="text-red-500">*</span></label>
                        <span class="text-[10px] text-gray-400" x-text="(quickReplyForm.text || '').length + ' chars'"></span>
                    </div>
                    <textarea
                        x-model="quickReplyForm.text"
                        rows="4"
                        placeholder="Type the exact text to send or insert when answering customer inquiries..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs focus:outline-none focus:border-black/30 focus:bg-white transition-all resize-y"
                    ></textarea>
                    <p class="text-[10px] text-gray-400 mt-1">Tip: Keep your message polite and clear. You can customize or add customer details before hitting send.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                <button
                    type="button"
                    @click="showAddEditModal = false"
                    class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition-all cursor-pointer"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    @click="saveQuickReplyItem()"
                    class="px-5 py-2 rounded-xl bg-[#C0422A] hover:bg-black text-white text-xs font-bold transition-all shadow-xs cursor-pointer"
                >
                    Save Template
                </button>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <!-- FLOATING TOAST NOTIFICATION                                         -->
    <!-- ═══════════════════════════════════════════════════════════════════ -->
    <div
        x-show="quickReplyToast"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="absolute bottom-5 right-5 bg-black text-white px-4 py-2.5 rounded-2xl shadow-xl text-xs font-bold flex items-center gap-2 z-50 pointer-events-none"
    >
        <x-lucide name="check" class="w-4 h-4 text-emerald-400" />
        <span x-text="quickReplyToast"></span>
    </div>

</div>
@endsection
