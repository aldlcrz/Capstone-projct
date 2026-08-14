<script type="application/json" id="chat-widget-config">
{!! json_encode([
    'currentUserId' => (string) (Auth::id() ?? ''),
    'isLoggedIn' => (bool) Auth::check(),
    'openChat' => session()->has('open_chat') ? session('open_chat') : null,
]) !!}
</script>

<script>
document.addEventListener('alpine:init', () => {
    const _chatConfig = JSON.parse(document.getElementById('chat-widget-config')?.textContent || '{}');
    if (_chatConfig.openChat) {
        window._autoOpenChat = _chatConfig.openChat;
    }

    Alpine.data('chatWidget', () => ({
        isOpen: false,
        mainMode: 'ai', // 'ai' or 'artisan'
        activeTab: 'conversations',
        conversations: [],
        messages: [],
        activeUser: null,
        newMessage: '',
        currentUserId: _chatConfig.currentUserId || '',
        isLoggedIn: Boolean(_chatConfig.isLoggedIn),
        pollInterval: null,

        // Smart Support State
        aiInput: '',
        aiLoading: false,
        aiMessages: [
            {
                role: 'assistant',
                text: 'Mabuhay! I am your **Lumbarong Smart Assistant** from Lumban, Laguna. How may I assist you today? You can ask me for wedding recommendations, fabric comparisons (Piña vs. Jusi vs. Cocoon), or budget-friendly graduation Barongs.',
                products: []
            }
        ],

        init() {
            window.addEventListener('toggle-chat', () => {
                this.isOpen = !this.isOpen;
                if (this.isOpen && this.mainMode === 'artisan' && this.isLoggedIn) {
                    this.loadConversations();
                }
            });

            window.addEventListener('open-chat', (e) => {
                this.isOpen = true;
                this.mainMode = 'artisan';
                this.startConversation(e.detail.sellerId, e.detail.sellerName);
            });

            if (window._autoOpenChat && window._autoOpenChat.sellerId) {
                const autoData = window._autoOpenChat;
                delete window._autoOpenChat;
                setTimeout(() => {
                    this.isOpen = true;
                    this.mainMode = 'artisan';
                    this.startConversation(autoData.sellerId, autoData.sellerName || 'Artisan');
                }, 300);
            }
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        // --- AI Stylist Methods ---
        sendAiPrompt(promptText) {
            this.aiInput = promptText;
            this.sendAiMessage();
        },

        sendAiMessage() {
            const query = (this.aiInput || '').trim();
            if (!query || this.aiLoading) return;

            this.aiMessages.push({ role: 'user', text: query, products: [] });
            this.aiInput = '';
            this.aiLoading = true;
            this.scrollAiToBottom();

            fetch('/ai/stylist/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken()
                },
                body: JSON.stringify({ message: query })
            })
            .then(async res => {
                if (!res.ok) {
                    const errText = await res.text();
                    throw new Error(`HTTP ${res.status}: ${errText}`);
                }
                return res.json();
            })
            .then(data => {
                this.aiMessages.push({
                    role: 'assistant',
                    text: data.reply || 'Here are our handcrafted Lumban Barong recommendations.',
                    products: data.products || []
                });
                this.scrollAiToBottom();
            })
            .catch((err) => {
                console.error('Smart Assistance request failed:', err);
                this.aiMessages.push({
                    role: 'assistant',
                    text: 'I apologize, but I encountered a momentary connection glitch. Piña-Seda and Cocoon Barongs remain our top recommendation for formal events!',
                    products: []
                });
            })
            .finally(() => {
                this.aiLoading = false;
                this.scrollAiToBottom();
            });
        },

        scrollAiToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.aiMsgBox;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        // --- Artisan Chat Methods ---
        loadConversations() {
            if (!this.isLoggedIn) return;
            fetch('/api/chat/conversations')
                .then(res => res.json())
                .then(data => { this.conversations = data; })
                .catch(() => {});
        },

        startConversation(id, name) {
            if (!this.isLoggedIn) {
                window.dispatchEvent(new CustomEvent('open-auth-gate', { detail: { message: 'Please log in to chat with artisans.' } }));
                return;
            }
            this.activeUser = { id, name };
            this.activeTab = 'messages';
            this.loadMessages();
            this.startPolling();
        },

        loadMessages() {
            if (!this.activeUser || !this.isLoggedIn) return;
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
    class="fixed bottom-20 lg:bottom-22 right-3 sm:right-6 w-[calc(100vw-1.5rem)] sm:w-[400px] max-w-[calc(100vw-1.5rem)] h-[550px] max-h-[calc(100vh-7rem)] bg-white rounded-3xl shadow-2xl border border-gray-150 flex flex-col z-60 overflow-hidden"
    style="display: none;"
    x-cloak
>
    <!-- Header with Tab Switcher -->
    <div class="px-5 py-3.5 bg-black text-white shrink-0 shadow-md">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2.5">
                <template x-if="mainMode === 'artisan' && activeTab === 'messages'">
                    <button @click="backToConversations()" class="p-1 hover:bg-white/10 rounded-lg transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                </template>
                <div>
                    <h3 class="font-serif text-sm font-bold tracking-wide flex items-center gap-1.5">
                        <span x-show="mainMode === 'ai'">Lumbarong Smart Assistance</span>
                        <span x-show="mainMode === 'artisan'" x-text="activeTab === 'messages' && activeUser ? activeUser.name : 'Artisan Messages'"></span>
                    </h3>
                    <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                        <span x-show="mainMode === 'ai'">Heritage Fashion & Shopping Advisor</span>
                        <span x-show="mainMode === 'artisan'">Direct Workshop Connection</span>
                    </p>
                </div>
            </div>
            <button @click="closeChat()" class="p-1.5 hover:bg-white/10 rounded-xl transition-colors cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Mode Toggle Pills -->
        <div class="flex bg-white/10 p-1 rounded-xl gap-1 text-[11px] font-bold">
            <button type="button" 
                    @click="mainMode = 'ai'"
                    :class="mainMode === 'ai' ? 'bg-[#C0422A] text-white shadow-sm' : 'text-gray-300 hover:text-white'"
                    class="flex-1 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                <span>Smart Support</span>
            </button>
            <button type="button" 
                    @click="mainMode = 'artisan'; if(isLoggedIn) { loadConversations(); } else { window.dispatchEvent(new CustomEvent('open-auth-gate', { detail: { message: 'Please log in to chat with artisans.' } })); }"
                    :class="mainMode === 'artisan' ? 'bg-[#C0422A] text-white shadow-sm' : 'text-gray-300 hover:text-white'"
                    class="flex-1 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                <span>💬</span>
                <span>Artisans</span>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SMART SUPPORT TAB -->
    <!-- ========================================== -->
    <div x-show="mainMode === 'ai'" class="flex-1 flex flex-col min-h-0 bg-[#FBF9F6]">
        <!-- Quick Prompt Chips -->
        <div class="px-4 py-2 bg-white border-b border-gray-100 flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
            <button @click="sendAiPrompt('Recommend a Barong for a groom/wedding')" class="whitespace-nowrap px-2.5 py-1 bg-amber-50 text-amber-900 hover:bg-amber-100 rounded-full text-[10px] font-bold border border-amber-200/60 transition-colors cursor-pointer">
                🤵 Groom / Wedding
            </button>
            <button @click="sendAiPrompt('What is the difference between Piña and Jusi?')" class="whitespace-nowrap px-2.5 py-1 bg-amber-50 text-amber-900 hover:bg-amber-100 rounded-full text-[10px] font-bold border border-amber-200/60 transition-colors cursor-pointer">
                🧵 Piña vs Jusi
            </button>
            <button @click="sendAiPrompt('Show graduation Barongs under ₱3,500')" class="whitespace-nowrap px-2.5 py-1 bg-amber-50 text-amber-900 hover:bg-amber-100 rounded-full text-[10px] font-bold border border-amber-200/60 transition-colors cursor-pointer">
                🎓 Graduation under ₱3.5k
            </button>
            <button @click="sendAiPrompt('Recommend a Barong for Ninong / Sponsor')" class="whitespace-nowrap px-2.5 py-1 bg-amber-50 text-amber-900 hover:bg-amber-100 rounded-full text-[10px] font-bold border border-amber-200/60 transition-colors cursor-pointer">
                👔 Ninong Attire
            </button>
        </div>

        <!-- Chat Stream -->
        <div x-ref="aiMsgBox" class="flex-1 overflow-y-auto no-scrollbar p-4 space-y-3.5">
            <template x-for="(msg, idx) in aiMessages" :key="idx">
                <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                    <div class="max-w-[85%] px-4 py-3 rounded-2xl text-xs leading-relaxed"
                         :class="msg.role === 'user' 
                             ? 'bg-black text-white rounded-tr-none shadow-sm' 
                             : 'bg-white text-gray-800 rounded-tl-none border border-gray-150 shadow-xs prose prose-xs'"
                         x-html="msg.text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>')">
                    </div>

                    <!-- Recommended Product Cards -->
                    <template x-if="msg.products && msg.products.length > 0">
                        <div class="w-full mt-2 grid grid-cols-1 gap-2">
                            <template x-for="prod in msg.products" :key="prod.id">
                                <a :href="prod.url" class="flex items-center gap-3 p-2 bg-white rounded-xl border border-gray-150 hover:border-[#C0422A] hover:shadow-md transition-all group">
                                    <img :src="prod.image || '/uploads/products/default.jpg'" class="w-12 h-12 rounded-lg object-cover bg-gray-50 border border-gray-100">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-gray-900 group-hover:text-[#C0422A] truncate" x-text="prod.name"></h4>
                                        <p class="text-[9px] font-semibold text-gray-500 truncate" x-text="prod.fabric"></p>
                                        <p class="text-xs font-black text-[#C0422A] mt-0.5">₱<span x-text="prod.price"></span></p>
                                    </div>
                                    <span class="text-[9px] font-bold text-[#C0422A] uppercase tracking-wider px-2 py-1 bg-red-50 rounded-lg group-hover:bg-[#C0422A] group-hover:text-white transition-colors shrink-0">View</span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Loading indicator -->
            <div x-show="aiLoading" class="flex items-center gap-2 text-xs text-gray-400 italic" x-cloak>
                <span class="inline-block w-2 h-2 rounded-full bg-[#C0422A] animate-ping"></span>
                <span>Smart support is preparing advice...</span>
            </div>
        </div>

        <!-- Input Bar -->
        <div class="p-3 bg-white border-t border-gray-100 flex items-center gap-2 shrink-0">
            <input type="text"
                   x-model="aiInput"
                   @keyup.enter="sendAiMessage()"
                   placeholder="Ask about fabrics, fit, or wedding attire..."
                   class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs outline-none focus:border-[#C0422A] focus:bg-white transition-all">
            <button @click="sendAiMessage()"
                    :disabled="!aiInput.trim() || aiLoading"
                    class="w-9 h-9 bg-[#C0422A] hover:bg-[#a33708] disabled:opacity-50 text-white rounded-xl flex items-center justify-center transition-colors shrink-0 cursor-pointer">
                <svg class="w-4 h-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9-7-9-7V19z"/></svg>
            </button>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- ARTISAN CHAT TAB -->
    <!-- ========================================== -->
    <div x-show="mainMode === 'artisan'" class="flex-1 flex flex-col min-h-0 bg-white">
        <!-- Conversations List -->
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
                                <span class="inline-block min-w-4 h-4 px-1 bg-red-500 text-white text-[8px] font-bold rounded-full text-center mt-1"
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
                        <p class="text-xs text-gray-400 italic">No direct artisan messages yet.</p>
                    </div>
                </template>
            </div>
        </div>

        <!-- Messages View -->
        <div x-show="activeTab === 'messages'" class="flex-1 flex flex-col min-h-0 bg-white">
            <div x-ref="msgBox" class="flex-1 overflow-y-auto no-scrollbar p-5 space-y-3">
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
                    <div class="py-16 text-center">
                        <p class="text-xs text-gray-400 italic">Type a message to start conversing with this artisan.</p>
                    </div>
                </template>
            </div>

            <!-- Input Bar -->
            <div class="p-3 border-t border-gray-100 flex items-center gap-2 bg-white shrink-0">
                <input
                    type="text"
                    x-model="newMessage"
                    @keyup.enter="sendMessage()"
                    placeholder="Type your message to artisan..."
                    class="flex-1 bg-gray-50 border border-gray-100 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:border-black/20"
                >
                <button
                    @click="sendMessage()"
                    class="w-9 h-9 bg-black text-white rounded-xl flex items-center justify-center hover:bg-gray-800 transition-colors shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9-7-9-7V19z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
