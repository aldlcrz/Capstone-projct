@auth
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('chatWidget', () => ({
        isOpen: false,
        activeTab: 'conversations',
        conversations: [],
        messages: [],
        activeUser: null,
        newMessage: '',
        currentUserId: '{{ Auth::id() }}',
        pollInterval: null,

        init() {
            window.addEventListener('toggle-chat', () => {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    if (this.activeUser) {
                        this.activeTab = 'messages';
                        this.loadMessages();
                        this.startPolling();
                    } else {
                        this.activeTab = 'conversations';
                        this.loadConversations();
                    }
                } else {
                    this.stopPolling();
                }
            });

            window.addEventListener('open-chat', (e) => {
                this.isOpen = true;
                this.startConversation(e.detail.sellerId, e.detail.sellerName);
            });
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

        startConversation(id, name) {
            this.activeUser = { id, name };
            this.activeTab = 'messages';
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
                const el = this.$refs.msgBox;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        backToConversations() {
            this.stopPolling();
            this.activeTab = 'conversations';
            this.activeUser = null;
            this.loadConversations();
        },

        closeChat() {
            this.isOpen = false;
            this.stopPolling();
        },

        formatTime(dateStr) {
            if (!dateStr) return '';
            try {
                return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            } catch { return ''; }
        }
    }));
});
</script>

<!-- Chat Widget Panel -->
<div
    x-data="chatWidget"
    x-show="isOpen"
    x-transition:enter="transition ease-out duration-300 transform"
    x-transition:enter-start="opacity-0 translate-y-12 scale-95"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-200 transform"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0 translate-y-12 scale-95"
    class="fixed bottom-24 right-6 w-96 h-[520px] bg-white rounded-3xl shadow-2xl border border-gray-100 flex flex-col z-999 overflow-hidden"
    style="display: none;"
>
    <!-- Header -->
    <div class="px-6 py-4 bg-black text-white flex items-center justify-between shadow-md shrink-0">
        <div class="flex items-center gap-3">
            <template x-if="activeTab === 'messages'">
                <button @click="backToConversations()" class="p-1 hover:bg-white/10 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
            </template>
            <div>
                <h3 class="font-serif text-sm font-bold tracking-wide"
                    x-text="activeTab === 'messages' && activeUser ? activeUser.name : 'LumBarong Messages'"></h3>
                <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5"
                   x-text="activeTab === 'messages' ? 'Active Conversation' : 'Chat with Artisans'"></p>
            </div>
        </div>
        <button @click="closeChat()" class="p-2 hover:bg-white/10 rounded-xl transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <!-- Conversations Tab -->
    <div x-show="activeTab === 'conversations'" class="flex-1 overflow-y-auto no-scrollbar p-4 bg-gray-50/50">
        <div class="space-y-2">
            <template x-for="conv in conversations" :key="conv.otherUser.id">
                <div
                    @click="startConversation(conv.otherUser.id, conv.otherUser.name)"
                    class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-100 hover:border-black/20 hover:shadow-md transition-all cursor-pointer"
                >
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-600 shrink-0 uppercase border border-gray-200"
                             x-text="conv.otherUser.name.charAt(0)"></div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-bold text-black truncate" x-text="conv.otherUser.name"></span>
                                <template x-if="conv.otherUser.role === 'seller'">
                                    <span class="px-1.5 py-0.5 bg-red-50 text-[#C0422A] text-[7px] font-black uppercase tracking-wider rounded border border-red-100 shrink-0">Seller</span>
                                </template>
                            </div>
                            <p class="text-[10px] text-gray-400 truncate leading-relaxed mt-0.5" x-text="conv.lastMessage"></p>
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-2">
                        <div class="text-[8px] font-bold text-gray-400" x-text="formatTime(conv.timestamp)"></div>
                        <template x-if="conv.unreadCount > 0">
                            <span class="inline-block min-w-[16px] h-4 px-1 bg-red-500 text-white text-[8px] font-bold rounded-full text-center mt-1"
                                  x-text="conv.unreadCount"></span>
                        </template>
                    </div>
                </div>
            </template>
            <template x-if="conversations.length === 0">
                <div class="py-20 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <p class="text-xs text-gray-400 italic">No conversations yet.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- Messages Tab -->
    <div x-show="activeTab === 'messages'" class="flex-1 flex flex-col min-h-0 bg-white">
        <div x-ref="msgBox" class="flex-1 overflow-y-auto no-scrollbar p-6 space-y-4">
            <template x-for="msg in messages" :key="msg.id">
                <div class="flex flex-col" :class="msg.senderId === currentUserId ? 'items-end' : 'items-start'">
                    <div class="max-w-[75%] px-4 py-3 rounded-2xl text-xs leading-relaxed"
                         :class="msg.senderId === currentUserId
                             ? 'bg-black text-white rounded-tr-none'
                             : 'bg-gray-100 text-gray-800 rounded-tl-none'"
                         x-text="msg.content"></div>
                    <span class="text-[8px] font-bold text-gray-400 mt-1" x-text="formatTime(msg.createdAt)"></span>
                </div>
            </template>
            <template x-if="messages.length === 0">
                <div class="py-20 text-center">
                    <p class="text-xs text-gray-400 italic">Start the conversation by typing a message below.</p>
                </div>
            </template>
        </div>

        <!-- Input -->
        <div class="p-4 border-t border-gray-100 flex items-center gap-2 bg-white shrink-0">
            <input
                type="text"
                x-model="newMessage"
                @keyup.enter="sendMessage()"
                placeholder="Type your message..."
                class="flex-1 bg-gray-50 border border-gray-100 rounded-xl px-4 py-3 text-xs focus:outline-none focus:border-black/20"
            >
            <button
                @click="sendMessage()"
                class="w-10 h-10 bg-black text-white rounded-xl flex items-center justify-center hover:bg-gray-800 transition-colors"
            >
                <svg class="w-4 h-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9-7-9-7V19z"/>
                </svg>
            </button>
        </div>
    </div>
</div>
@endauth
