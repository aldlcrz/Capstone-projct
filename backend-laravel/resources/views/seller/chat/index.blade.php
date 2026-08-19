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

        init() {
            this.loadConversations();
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
            fetch('/api/chat/conversations')
                .then(res => res.json())
                .then(data => { this.conversations = data; })
                .catch(() => {});
        },

        selectUser(user) {
            this.activeUser = user;
            this.loadMessages();
            this.startPolling();
        },

        loadMessages() {
            if (!this.activeUser) return;
            fetch('/api/chat/conversation/' + this.activeUser.id)
                .then(res => res.json())
                .then(data => {
                    this.messages = data;
                    this.scrollToBottom();
                })
                .catch(() => {});
        },

        sendMessage() {
            if (!this.newMessage.trim() || !this.activeUser) return;
            const content = this.newMessage;
            this.newMessage = '';

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
        }
    }));
});
</script>

<div
    x-data="sellerChat({ autoOpenUserId: '{{ $autoOpenUserId ?? '' }}', autoOpenUserName: '{{ addslashes($autoOpenUserName ?? 'Customer') }}', currentUserId: '{{ Auth::id() ?? '' }}' })"
    class="bg-white rounded-2xl sm:rounded-3xl border border-gray-100 shadow-sm overflow-hidden flex h-[calc(100vh-140px)] sm:h-[calc(100vh-180px)] min-h-112.5"
>
    <!-- Left Sidebar: Conversations -->
    <div 
        class="w-full md:w-80 border-r border-gray-100 flex flex-col bg-white h-full shrink-0"
        :class="activeUser ? 'hidden md:flex' : 'flex'"
    >
        <div class="p-4 sm:p-6 border-b border-gray-100 shrink-0">
            <h2 class="font-serif text-base sm:text-lg font-bold text-black uppercase">Inbox</h2>
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Customer Inquiries</p>
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
                                <img :src="conv.otherUser.profileImage" class="w-full h-full object-cover" @error="$el.style.display='none'">
                            </template>
                            <span x-show="!conv.otherUser || !conv.otherUser.profileImage" x-text="((conv.otherUser && conv.otherUser.name) || 'C').charAt(0)"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-black truncate" x-text="conv.otherUser && conv.otherUser.name ? conv.otherUser.name : 'Customer'"></div>
                            <p class="text-[10px] text-gray-400 truncate leading-relaxed mt-0.5" x-text="typeof conv.lastMessage === 'object' ? (conv.lastMessage.content || conv.lastMessage.body || '') : (conv.lastMessage || '')"></p>
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
                                <img :src="activeUser.profileImage" class="w-full h-full object-cover" @error="$el.style.display='none'">
                            </template>
                            <span x-show="!activeUser || !activeUser.profileImage" x-text="((activeUser && activeUser.name) || 'C').charAt(0)"></span>
                        </div>
                        <div>
                            <h3 class="font-serif text-sm font-bold text-black" x-text="activeUser.name"></h3>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">Customer Chat Thread</p>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div x-ref="threadBox" class="flex-1 overflow-y-auto no-scrollbar p-4 sm:p-8 space-y-4 bg-gray-50/10">
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex flex-col" :class="msg.senderId === currentUserId ? 'items-end' : 'items-start'">
                            <div class="max-w-[85%] sm:max-w-[70%] px-4 sm:px-5 py-3 rounded-2xl text-xs leading-relaxed"
                                 :class="msg.senderId === currentUserId
                                     ? 'bg-[#C0422A] text-white rounded-tr-none'
                                     : 'bg-gray-100 text-gray-800 rounded-tl-none border border-gray-200/50'"
                                 x-text="msg.content"></div>
                            <span class="text-[9px] font-medium text-gray-400 mt-1" x-text="formatDateTime(msg.createdAt)"></span>
                        </div>
                    </template>
                    <template x-if="messages.length === 0">
                        <div class="py-20 text-center">
                            <p class="text-xs text-gray-400 italic">No messages in thread.</p>
                        </div>
                    </template>
                </div>

                <!-- Reply Input -->
                <div class="p-3.5 sm:p-6 border-t border-gray-100 flex items-center gap-2 sm:gap-4 bg-white shrink-0">
                    <input
                        type="text"
                        x-model="newMessage"
                        @keyup.enter="sendMessage()"
                        placeholder="Type a response..."
                        class="flex-1 bg-gray-50 border border-gray-200 rounded-xl sm:rounded-2xl px-4 sm:px-6 py-3 sm:py-4 text-xs focus:outline-none focus:border-black/20"
                    >
                    <button
                        @click="sendMessage()"
                        class="px-4 sm:px-6 py-3 sm:py-4 bg-[#C0422A] text-white rounded-xl sm:rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-black transition-colors shrink-0"
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
            </div>
        </template>
    </div>
</div>
@endsection
