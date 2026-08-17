<script type="application/json" id="chat-widget-config">
{!! json_encode([
    'currentUserId' => (string) (Auth::id() ?? ''),
    'isLoggedIn' => (bool) Auth::check(),
    'openChat' => session()->has('open_chat') ? session('open_chat') : null,
]) !!}
</script>

<script>
(function() {
    function registerChatWidget() {
        if (window._chatWidgetRegistered) return;
        window._chatWidgetRegistered = true;

        const _chatConfig = JSON.parse(document.getElementById('chat-widget-config')?.textContent || '{}');
        if (_chatConfig.openChat) {
            window._autoOpenChat = _chatConfig.openChat;
        }

        const factory = () => ({
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

            // Smart Support State & Session Context Memory
            aiInput: '',
            aiLoading: false,
            sessionContext: {},
            aiMessages: [
                {
                    role: 'assistant',
                    text: 'Mabuhay! I am your **Lumbarong Smart Assistant** from Lumban, Laguna. How may I assist you today? You can ask me for wedding recommendations, fabric comparisons (Piña vs. Jusi vs. Cocoon), or live tracking for your recent orders.',
                    products: [],
                    refinements: [
                        { label: '🤵 Wedding Recommendations', prompt: 'Recommend a Barong for a wedding groom' },
                        { label: '🧵 Fabric Guide', prompt: 'What is the difference between Piña and Jusi?' },
                        { label: '🎓 Graduation under ₱3,500', prompt: 'Show graduation Barongs under ₱3,500' },
                        { label: '📦 Track My Order', prompt: 'Where is my order?' }
                    ]
                }
            ],

            init() {
                window.addEventListener('toggle-chat', () => {
                    this.toggleChat();
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

            toggleChat() {
                this.isOpen = !this.isOpen;
                if (this.isOpen && this.mainMode === 'artisan' && this.isLoggedIn) {
                    this.loadConversations();
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

                this.aiMessages.push({ role: 'user', text: query, products: [], refinements: [] });
                this.aiInput = '';
                this.aiLoading = true;
                this.scrollAiToBottom();

                const history = this.aiMessages.slice(-6).map(m => ({
                    role: m.role === 'user' ? 'user' : 'model',
                    text: m.text
                }));

                fetch('/ai/stylist/chat', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({ 
                        message: query,
                        history: history,
                        session_context: this.sessionContext
                    })
                })
                .then(async res => {
                    const data = await res.json();
                    if (!res.ok) {
                        throw new Error(data.message || 'Server error ' + res.status);
                    }
                    return data;
                })
                .then(data => {
                    this.aiLoading = false;
                    if (data.session_context) {
                        this.sessionContext = data.session_context;
                    }
                    const textReply = data.reply || data.message || "Mabuhay! I am your LumBarong Smart Assistant. How may I help you today?";
                    this.aiMessages.push({
                        role: 'assistant',
                        text: textReply,
                        products: data.products || [],
                        refinements: data.refinements || []
                    });
                    this.scrollAiToBottom();
                })
                .catch(err => {
                    this.aiLoading = false;
                    console.error('Smart Assistant error:', err);
                    this.aiMessages.push({
                        role: 'assistant',
                        text: 'Mabuhay! Hello and welcome to **LumBarong Smart Assistance**. I am your heritage styling advisor and shopping concierge from Lumban, Laguna.\n\nHow may I help you today? You can ask me about:\n• **Best Sellers** & Top recommended Barongs\n• **Fabric Guide** (Piña vs. Jusi vs. Cocoon vs. Organza)\n• **Event Styling** (Weddings, Grooms, Ninongs, Graduations)\n• **Care & Maintenance** (How to wash, iron, and store)',
                        products: [],
                        refinements: [
                            { label: '🤵 Wedding Recommendations', prompt: 'Recommend a Barong for a wedding groom' },
                            { label: '🧵 Fabric Guide', prompt: 'What is the difference between Piña and Jusi?' },
                            { label: '⭐ Best Sellers', prompt: 'Show me your best selling Barongs' }
                        ]
                    });
                    this.scrollAiToBottom();
                });
            },

            scrollAiToBottom() {
                this.$nextTick(() => {
                    const box = this.$refs.aiMsgBox;
                    if (box) box.scrollTop = box.scrollHeight;
                });
            },

            // --- Artisan Peer-to-Peer Chat Methods ---
            loadConversations() {
                if (!this.isLoggedIn) {
                    this.conversations = [];
                    return;
                }
                fetch('/chat/conversations', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Status ' + res.status);
                    return res.json();
                })
                .then(data => {
                    this.conversations = Array.isArray(data) ? data : [];
                })
                .catch(err => {
                    console.error('Failed to load conversations:', err);
                    this.conversations = [];
                });
            },

            startConversation(sellerId, sellerName) {
                if (!this.isLoggedIn) {
                    window.location.href = '/login';
                    return;
                }
                this.activeUser = {
                    id: sellerId,
                    name: sellerName || 'Artisan'
                };
                this.activeTab = 'messages';
                if (!Array.isArray(this.messages)) this.messages = [];
                this.loadMessages();

                if (this.pollInterval) clearInterval(this.pollInterval);
                this.pollInterval = setInterval(() => {
                    if (this.isOpen && this.mainMode === 'artisan' && this.activeTab === 'messages' && this.activeUser) {
                        this.loadMessages(true);
                    }
                }, 4000);
            },

            selectUser(user) {
                this.activeUser = user;
                this.activeTab = 'messages';
                if (!Array.isArray(this.messages)) this.messages = [];
                this.loadMessages();

                if (this.pollInterval) clearInterval(this.pollInterval);
                this.pollInterval = setInterval(() => {
                    if (this.isOpen && this.mainMode === 'artisan' && this.activeTab === 'messages' && this.activeUser) {
                        this.loadMessages(true);
                    }
                }, 4000);
            },

            backToConversations() {
                this.activeTab = 'conversations';
                this.activeUser = null;
                if (this.pollInterval) clearInterval(this.pollInterval);
                this.loadConversations();
            },

            loadMessages(isPolling = false) {
                if (!this.activeUser || !this.isLoggedIn) {
                    this.messages = [];
                    return;
                }

                fetch('/chat/messages/' + this.activeUser.id, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    }
                })
                .then(res => {
                    if (!res.ok) throw new Error('Status ' + res.status);
                    return res.json();
                })
                .then(data => {
                    this.messages = Array.isArray(data) ? data : [];
                    if (!isPolling) {
                        this.$nextTick(() => {
                            const box = this.$refs.artisanMsgBox;
                            if (box) box.scrollTop = box.scrollHeight;
                        });
                    }
                })
                .catch(err => {
                    console.error('Failed to load messages:', err);
                    if (!Array.isArray(this.messages)) this.messages = [];
                });
            },

            formatMessageTime(iso) {
                if (!iso) return '';
                try {
                    const d = new Date(iso);
                    if (isNaN(d.getTime())) return '';
                    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                } catch(e) {
                    return '';
                }
            },

            formatMessageDate(iso) {
                if (!iso) return '';
                try {
                    const d = new Date(iso);
                    if (isNaN(d.getTime())) return '';
                    return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
                } catch(e) {
                    return '';
                }
            },

            sendMessage() {
                const body = (this.newMessage || '').trim();
                if (!body || !this.activeUser || !this.isLoggedIn) return;

                if (!Array.isArray(this.messages)) {
                    this.messages = [];
                }

                const tempMsg = {
                    id: 'temp-' + Date.now(),
                    senderId: this.currentUserId,
                    receiverId: this.activeUser.id,
                    content: body,
                    body: body,
                    createdAt: new Date().toISOString(),
                    created_at: new Date().toISOString()
                };
                this.messages.push(tempMsg);
                this.newMessage = '';
                
                this.$nextTick(() => {
                    const box = this.$refs.artisanMsgBox;
                    if (box) box.scrollTop = box.scrollHeight;
                });

                fetch('/chat/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken()
                    },
                    body: JSON.stringify({
                        receiverId: this.activeUser.id,
                        content: body,
                        body: body
                    })
                })
                .then(res => res.json())
                .then(savedMsg => {
                    if (!Array.isArray(this.messages)) this.messages = [];
                    const idx = this.messages.findIndex(m => m.id === tempMsg.id);
                    if (idx !== -1 && savedMsg && savedMsg.id) {
                        this.messages[idx] = savedMsg;
                    }
                })
                .catch(err => {
                    console.error('Failed to send message:', err);
                });
            },

            closeChat() {
                this.isOpen = false;
                if (this.pollInterval) clearInterval(this.pollInterval);
            }
        });

        if (window.Alpine) {
            window.Alpine.data('chatWidget', factory);
        } else {
            document.addEventListener('alpine:init', () => {
                window.Alpine.data('chatWidget', factory);
            });
        }
    }

    registerChatWidget();
    document.addEventListener('DOMContentLoaded', registerChatWidget);
})();
</script>

<style>
.lumbarong-chat-wrapper {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 99999;
}
.lumbarong-chat-window {
    position: fixed;
    right: 24px;
    bottom: 90px;
    width: 380px;
    max-width: calc(100vw - 32px);
    height: 560px;
    max-height: calc(100vh - 110px);
    z-index: 99999;
    box-shadow: 0 20px 40px rgba(0,0,0,0.25);
}

/* Mobile Screens: Position floating button cleanly above the bottom navigation bar */
@media (max-width: 1023px) {
    .lumbarong-chat-wrapper {
        bottom: calc(82px + env(safe-area-inset-bottom, 0px)) !important;
        right: 16px !important;
    }
    .lumbarong-chat-window {
        bottom: calc(148px + env(safe-area-inset-bottom, 0px)) !important;
        right: 12px !important;
        left: 12px !important;
        width: auto !important;
        max-width: none !important;
        height: calc(100dvh - 170px) !important;
        max-height: 560px !important;
    }
}
</style>

<div x-data="chatWidget" class="lumbarong-chat-wrapper">
    <!-- Floating Trigger Button -->
    <button 
        type="button"
        @click="toggleChat()"
        style="width: 56px; height: 56px; background-color: #1F1F1F; box-shadow: 0 10px 25px rgba(0,0,0,0.35); border: 2px solid rgba(255,255,255,0.25);"
        class="rounded-full text-white flex items-center justify-center hover:scale-105 active:scale-95 transition-all duration-300 relative group cursor-pointer"
        aria-label="Open LumBarong Support & Chat"
    >
        <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C0422A] opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-[#C0422A]"></span>
        </span>
        <svg x-show="!isOpen" style="width: 24px; height: 24px;" class="text-white transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
        </svg>
        <svg x-show="isOpen" x-cloak style="width: 24px; height: 24px;" class="text-white transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <!-- Chat Window Container -->
    <div 
        x-show="isOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="lumbarong-chat-window bg-white rounded-3xl border border-gray-200 flex flex-col overflow-hidden"
        x-cloak
    >
        <!-- Header -->
        <div class="bg-[#3D2B1F] text-white p-4 shrink-0 flex flex-col gap-2.5">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <template x-if="mainMode === 'artisan' && activeTab === 'messages'">
                        <button @click="backToConversations()" class="p-1 hover:bg-white/10 rounded-lg transition-colors cursor-pointer shrink-0" title="Back to conversations">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                    </template>
                    <div class="min-w-0">
                        <h3 class="font-serif text-sm font-bold tracking-wide flex items-center gap-1.5 truncate">
                            <span x-show="mainMode === 'ai'">LumBarong Smart Assistant</span>
                            <span x-show="mainMode === 'artisan'" x-text="activeTab === 'messages' && activeUser ? activeUser.name : 'Artisan Messages'" class="truncate"></span>
                        </h3>
                        <p class="text-[8px] font-bold text-gray-400 uppercase tracking-widest mt-0.5 truncate">
                            <span x-show="mainMode === 'ai'">Heritage Fashion &amp; Shopping Concierge</span>
                            <span x-show="mainMode === 'artisan'">Direct Workshop Connection</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <!-- Visit Shop Button (visible when chatting with artisan) -->
                    <template x-if="mainMode === 'artisan' && activeTab === 'messages' && activeUser && activeUser.id">
                        <a :href="'/shops/' + activeUser.id"
                           target="_blank"
                           title="Visit Artisan Shop"
                           class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#C0422A] hover:bg-[#A33520] text-white text-[10px] font-extrabold uppercase tracking-wider rounded-lg transition-all shadow-xs border border-white/20">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                            <span>Visit Shop</span>
                        </a>
                    </template>

                    <button @click="closeChat()" class="p-1.5 hover:bg-white/10 rounded-xl transition-colors cursor-pointer text-gray-300 hover:text-white" title="Close">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mode Toggle Pills -->
            <div class="flex bg-white/10 p-1 rounded-xl gap-1 text-[11px] font-bold">
                <button type="button" 
                        @click="mainMode = 'ai'"
                        :class="mainMode === 'ai' ? 'bg-[#C0422A] text-white shadow-sm' : 'text-gray-300 hover:text-white'"
                        class="flex-1 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>✨ Smart Assistant</span>
                </button>
                <button type="button" 
                        @click="mainMode = 'artisan'; if(isLoggedIn) { loadConversations(); } else { window.dispatchEvent(new CustomEvent('open-auth-gate', { detail: { message: 'Please log in to chat with artisans.' } })); }"
                        :class="mainMode === 'artisan' ? 'bg-[#C0422A] text-white shadow-sm' : 'text-gray-300 hover:text-white'"
                        class="flex-1 py-1.5 rounded-lg transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                    <span>💬 Artisans</span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SMART ASSISTANT TAB (3-MODE RECOMMENDATIONS) -->
        <!-- ========================================== -->
        <div x-show="mainMode === 'ai'" class="flex-1 flex flex-col min-h-0 bg-[#FAF7F2]">
            <!-- Quick Starter Prompt Chips -->
            <div class="px-3.5 py-2 bg-white border-b border-[#EBE3D9] flex items-center gap-1.5 overflow-x-auto no-scrollbar shrink-0">
                <button @click="sendAiPrompt('Recommend a Barong for a wedding groom')" class="whitespace-nowrap px-2.5 py-1 bg-[#F7F3EE] hover:bg-[#E5DDD5] text-[#3D2B1F] rounded-full text-[10px] font-bold border border-[#E5DDD5] transition-colors cursor-pointer">
                    🤵 Wedding Groom
                </button>
                <button @click="sendAiPrompt('What is the difference between Piña and Jusi?')" class="whitespace-nowrap px-2.5 py-1 bg-[#F7F3EE] hover:bg-[#E5DDD5] text-[#3D2B1F] rounded-full text-[10px] font-bold border border-[#E5DDD5] transition-colors cursor-pointer">
                    🧵 Piña vs Jusi
                </button>
                <button @click="sendAiPrompt('Show graduation Barongs under ₱3,500')" class="whitespace-nowrap px-2.5 py-1 bg-[#F7F3EE] hover:bg-[#E5DDD5] text-[#3D2B1F] rounded-full text-[10px] font-bold border border-[#E5DDD5] transition-colors cursor-pointer">
                    🎓 Graduation under ₱3.5k
                </button>
                <button @click="sendAiPrompt('Where is my order?')" class="whitespace-nowrap px-2.5 py-1 bg-[#F7F3EE] hover:bg-[#E5DDD5] text-[#3D2B1F] rounded-full text-[10px] font-bold border border-[#E5DDD5] transition-colors cursor-pointer">
                    📦 Track Order
                </button>
            </div>

            <!-- Chat Messages Stream -->
            <div x-ref="aiMsgBox" class="flex-1 overflow-y-auto no-scrollbar p-3.5 space-y-4">
                <template x-for="(msg, idx) in aiMessages" :key="idx">
                    <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                        <!-- Message Bubble -->
                        <div class="max-w-[90%] px-4 py-3 rounded-2xl text-xs leading-relaxed"
                             :class="msg.role === 'user' 
                                 ? 'bg-[#3D2B1F] text-white rounded-tr-none shadow-sm' 
                                 : 'bg-white text-gray-800 rounded-tl-none border border-[#E5DDD5] shadow-xs prose prose-xs'"
                             x-html="msg.text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>').replace(/\n/g, '<br>')">
                        </div>

                        <!-- Scored Recommendation Product Cards -->
                        <template x-if="msg.products && msg.products.length > 0">
                            <div class="w-full mt-2.5 space-y-2.5">
                                <template x-for="prod in msg.products" :key="prod.id">
                                    <div class="bg-white rounded-2xl border border-[#E5DDD5] p-3 shadow-xs hover:border-[#C0422A] hover:shadow-md transition-all">
                                        <!-- Card Header with Tier Badge & Score -->
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                                  :class="prod.tier === 'best' ? 'bg-amber-100 text-amber-900 border border-amber-200' : (prod.tier === 'budget' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200' : 'bg-blue-100 text-blue-900 border border-blue-200')"
                                                  x-text="prod.badge"></span>
                                            <span class="text-[10px] font-mono font-bold text-[#C0422A] bg-red-50 px-2 py-0.5 rounded-md"
                                                  x-text="prod.score + '% Match'"></span>
                                        </div>

                                        <!-- Product Snapshot -->
                                        <div class="flex items-center gap-3">
                                            <img :src="prod.image || '/uploads/products/default.jpg'" 
                                                 class="w-14 h-14 rounded-xl object-cover bg-gray-50 border border-gray-100 shrink-0">
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-xs font-bold text-[#3D2B1F] truncate" x-text="prod.name"></h4>
                                                <p class="text-[10px] font-medium text-gray-500 truncate" x-text="prod.fabric"></p>
                                                <p class="text-xs font-black text-[#C0422A] font-mono mt-0.5">₱<span x-text="prod.price"></span></p>
                                            </div>
                                            <a :href="prod.url" 
                                               class="px-3 py-1.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-[10px] font-bold rounded-xl uppercase tracking-wider transition-colors shrink-0">
                                                View
                                            </a>
                                        </div>

                                        <!-- Why this matches checklist -->
                                        <template x-if="prod.reasons && prod.reasons.length > 0">
                                            <div class="mt-2.5 pt-2 border-t border-[#F0EAE1] space-y-1">
                                                <div class="text-[8px] font-bold text-gray-400 uppercase tracking-widest">Why this match:</div>
                                                <template x-for="(reason, rIdx) in prod.reasons" :key="rIdx">
                                                    <div class="text-[10px] text-gray-600 font-medium flex items-start gap-1.5 leading-tight">
                                                        <span class="text-emerald-600 font-bold">✓</span>
                                                        <span x-text="reason.replace(/^✓\s*/, '')"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Dynamic Refinement Chips -->
                        <template x-if="msg.refinements && msg.refinements.length > 0">
                            <div class="w-full mt-2 flex flex-wrap gap-1.5">
                                <template x-for="(chip, cIdx) in msg.refinements" :key="cIdx">
                                    <button type="button" 
                                            @click="sendAiPrompt(chip.prompt)"
                                            class="px-2.5 py-1 bg-white hover:bg-[#F7F3EE] text-[#3D2B1F] border border-[#E5DDD5] hover:border-[#C0422A] rounded-xl text-[10px] font-semibold transition-all cursor-pointer shadow-2xs">
                                        <span x-text="chip.label"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Loading indicator -->
                <div x-show="aiLoading" class="flex items-center gap-2 text-xs text-gray-400 italic p-2" x-cloak>
                    <span class="inline-block w-2 h-2 rounded-full bg-[#C0422A] animate-ping"></span>
                    <span>Smart Assistant is analyzing Lumban collections...</span>
                </div>
            </div>

            <!-- Input Bar -->
            <div class="p-3 bg-white border-t border-[#EBE3D9] flex items-center gap-2 shrink-0">
                <input type="text"
                       x-model="aiInput"
                       @keyup.enter="sendAiMessage()"
                       placeholder="Ask about wedding barongs, budget, fabric, or order status..."
                       autocomplete="off"
                       autocorrect="off"
                       spellcheck="false"
                       inputmode="text"
                       class="flex-1 bg-[#FAF7F2] border border-[#EBE3D9] text-[#3D2B1F] rounded-xl px-4 py-2.5 text-xs outline-none focus:border-[#C0422A] focus:bg-white transition-all">
                <button @click="sendAiMessage()"
                        :disabled="!aiInput.trim() || aiLoading"
                        class="w-9 h-9 bg-[#3D2B1F] hover:bg-[#C0422A] disabled:opacity-50 text-white rounded-xl flex items-center justify-center transition-colors shrink-0 cursor-pointer">
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
                    <template x-for="(conv, cIdx) in (Array.isArray(conversations) ? conversations : [])" :key="conv && conv.otherUser && conv.otherUser.id ? conv.otherUser.id : cIdx">
                        <template x-if="conv && conv.otherUser">
                            <div
                                @click="startConversation(conv.otherUser.id, conv.otherUser.name || 'Artisan')"
                                class="flex items-center justify-between p-4 bg-white rounded-2xl border border-gray-100 hover:border-black/20 hover:shadow-md transition-all cursor-pointer"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-600 shrink-0 uppercase border border-gray-200"
                                         x-text="(conv.otherUser.name || 'A').charAt(0)"></div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-gray-900 truncate" x-text="conv.otherUser.name || 'Artisan'"></div>
                                        <div class="text-[11px] text-gray-500 truncate" x-text="conv.lastMessage ? (conv.lastMessage.body || conv.lastMessage.content || conv.lastMessage || '') : ''"></div>
                                    </div>
                                </div>
                                <div class="text-[9px] text-gray-400 font-medium shrink-0 ml-2" 
                                     x-text="formatMessageDate(conv.timestamp || (conv.lastMessage && (conv.lastMessage.createdAt || conv.lastMessage.created_at)))"></div>
                            </div>
                        </template>
                    </template>
                    <div x-show="!conversations || conversations.length === 0" class="text-center py-12 text-gray-400 text-xs italic">
                        No conversations yet. Visit an artisan shop page to start chatting!
                    </div>
                </div>
            </div>

            <!-- Messages Stream -->
            <div x-show="activeTab === 'messages'" class="flex-1 flex flex-col min-h-0">
                <div x-ref="artisanMsgBox" class="flex-1 overflow-y-auto no-scrollbar p-4 space-y-3 bg-[#FAF7F2]/50">
                    <template x-for="(msg, mIdx) in (Array.isArray(messages) ? messages : [])" :key="msg.id || mIdx">
                        <div class="flex flex-col" :class="String(msg.senderId) === String(currentUserId) ? 'items-end' : 'items-start'">
                            <div class="max-w-[82%] px-4 py-2.5 rounded-2xl text-xs leading-relaxed"
                                 :class="String(msg.senderId) === String(currentUserId) 
                                     ? 'bg-[#3D2B1F] text-white rounded-tr-none shadow-sm' 
                                     : 'bg-white text-gray-900 rounded-tl-none border border-gray-200 shadow-xs'"
                                 x-text="msg.content || msg.body || ''">
                            </div>
                            <span class="text-[9px] text-gray-400 mt-1 px-1 font-medium" 
                                  x-text="formatMessageTime(msg.createdAt || msg.created_at || msg.timestamp)"></span>
                        </div>
                    </template>
                </div>

                <!-- Message Input -->
                <div class="p-3 bg-white border-t border-gray-100 flex items-center gap-2 shrink-0">
                    <input type="text"
                           x-model="newMessage"
                           @keyup.enter="sendMessage()"
                           placeholder="Message artisan..."
                           class="flex-1 bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-xs outline-none focus:border-[#3D2B1F] focus:bg-white transition-all">
                    <button @click="sendMessage()"
                            :disabled="!newMessage.trim()"
                            class="w-9 h-9 bg-[#3D2B1F] hover:bg-[#C0422A] disabled:opacity-50 text-white rounded-xl flex items-center justify-center transition-colors shrink-0 cursor-pointer">
                        <svg class="w-4 h-4 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9-7-9-7V19z"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
