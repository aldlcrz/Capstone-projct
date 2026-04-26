"use client";
import React, { useState, useEffect, useRef } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import AdminLayout from "@/components/AdminLayout";
import SellerLayout from "@/components/SellerLayout";
import { MessageCircle, Store, Send, Loader2, MoreVertical, ChevronLeft } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import Image from "next/image";
import { useSearchParams } from "next/navigation";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { resolveBackendImageUrl } from "@/lib/productImages";

export default function MessagesPage() {
  return (
    <React.Suspense fallback={<div className="h-screen flex items-center justify-center">Sychronizing Registry...</div>}>
      <MessagesThreadManager />
    </React.Suspense>
  );
}

function MessagesThreadManager() {
  const searchParams = useSearchParams();
  const sellerIdParam = searchParams.get("sellerId");
  const sellerNameParam = searchParams.get("sellerName");
  const productIdParam = searchParams.get("productId");
  const productNameParam = searchParams.get("productName");
  const productImageParam = searchParams.get("productImage");
  const productPriceParam = searchParams.get("productPrice");

  const [threads, setThreads] = useState([]);
  const [activeThread, setActiveThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [isSending, setIsSending] = useState(false);
  const [typingStatus, setTypingStatus] = useState({ isTyping: false, senderId: null });
  const [userRole, setUserRole] = useState(null);
  const [isUnauthorized, setIsUnauthorized] = useState(false);

  useEffect(() => {
    try {
      // Strictly use customer_user for storefront messages to prevent admin/seller session hijacking
      const customerData = getStoredUserForRole("customer");
      setUserRole(customerData?.role || "customer");
    } catch (e) {
      setUserRole("customer");
    }

    // Solve double scrollbar issue by locking the outer body and layout main container
    document.body.style.overflow = 'hidden';
    const mainEl = document.querySelector('main');
    if (mainEl) {
      mainEl.style.overflow = 'hidden';
      mainEl.style.height = '100dvh'; // Lock height to prevent bounce
    }

    return () => {
      document.body.style.overflow = 'auto';
      if (mainEl) {
        mainEl.style.overflow = 'auto';
        mainEl.style.height = 'auto';
      }
    };
  }, []);

  const { socket } = useSocket();

  useEffect(() => {
    const fetchThreads = async () => {
      try {
        const res = await api.get("/chat/threads");
        let allThreads = res.data;

        // Handle incoming sellerId from inquiry
        if (sellerIdParam) {
          const existingThread = allThreads.find(t => String(t.otherUser?.id) === String(sellerIdParam));
          if (existingThread) {
            setActiveThread(existingThread);
            fetchMessages(existingThread);
          } else {
            // Create a "ghost" thread for new inquiry
            const ghostThread = {
              otherUser: { id: sellerIdParam, name: sellerNameParam || "Artisan Workshop", role: "seller" },
              isGhost: true,
              productContext: productIdParam ? {
                id: productIdParam,
                name: productNameParam,
                image: productImageParam,
                price: productPriceParam
              } : null
            };
            setActiveThread(ghostThread);
            setMessages([]);
            if (productNameParam) {
              setNewMessage(`I am inquiring about this masterpiece: ${productNameParam}. Is it currently available for my heritage collection?`);
            }
          }
        } else if (allThreads.length > 0) {
          // Only auto-select first thread on desktop (>= 1024px)
          if (typeof window !== 'undefined' && window.innerWidth >= 1024) {
            setActiveThread(allThreads[0]);
            fetchMessages(allThreads[0]);
          }
        }

        setThreads(allThreads);
      } catch (err) {
        if (err?.response?.status === 401) {
          setIsUnauthorized(true);
        } else {
          console.error("Failed to fetch threads", err);
        }
      } finally {
        setLoading(false);
      }
    };
    fetchThreads();

    if (socket) {
      const handleIncomingMessage = (msg) => {
        // Use functional state updates to avoid stale closures with activeThread
        setActiveThread(current => {
          if (current && (
            String(msg.senderId) === String(current.otherUser?.id) ||
            String(msg.receiverId) === String(current.otherUser?.id)
          )) {
            setMessages(prev => {
              // Deduplicate: Don't add if already exists (avoids double append for my own sent messages)
              if (prev.some(m => m.id === msg.id)) return prev;
              return [...prev, msg];
            });
          }
          return current;
        });

        // Always update sidebar
        api.get("/chat/threads").then(res => setThreads(res.data)).catch(() => { });
      };

      socket.on("receive_message", handleIncomingMessage);

      const handleTyping = (data) => {
        setActiveThread(current => {
          if (current && String(data.senderId) === String(current.otherUser?.id)) {
            setTypingStatus(data);
            setTimeout(() => setTypingStatus({ isTyping: false, senderId: null }), 3000);
          }
          return current;
        });
      };

      socket.on("typing_status", handleTyping);
    }

    return () => {
      if (socket) {
        socket.off("receive_message");
        socket.off("typing_status");
      }
    };
  }, [socket, sellerIdParam, sellerNameParam, productIdParam, productNameParam, productImageParam, productPriceParam]);

  const fetchMessages = async (thread) => {
    setActiveThread(thread);
    if (thread.isGhost) {
      setMessages([]);
      return;
    }
    try {
      const res = await api.get(`/chat/conversation/${thread.otherUser.id}`);
      setMessages(res.data);
    } catch (err) {
      console.error("Failed to fetch messages");
    }
  };

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !activeThread || isSending) return;

    setIsSending(true);
    try {
      const res = await api.post("/chat/send", {
        receiverId: activeThread.otherUser.id,
        content: newMessage
      });

      if (activeThread.isGhost) {
        const realThread = { ...activeThread, isGhost: false };
        setThreads(prev => [realThread, ...prev.filter(t => t.otherUser?.id !== activeThread.otherUser?.id)]);
        setActiveThread(realThread);
      }

      setMessages(prev => {
        if (prev.some(m => m.id === res.data.id)) return prev;
        return [...prev, res.data];
      });
      setNewMessage("");
    } catch (err) {
      console.error("Failed to send message");
    } finally {
      setIsSending(false);
    }
  };

  return (
    <MessagesUI
      threads={threads}
      activeThread={activeThread}
      setActiveThread={setActiveThread}
      messages={messages}
      setMessages={setMessages}
      newMessage={newMessage}
      setNewMessage={setNewMessage}
      loading={loading}
      isSending={isSending}
      typingStatus={typingStatus}
      fetchMessages={fetchMessages}
      handleSendMessage={handleSendMessage}
      userRole={userRole}
      isUnauthorized={isUnauthorized}
    />
  );
}

function MessagesUI({
  threads,
  activeThread,
  setActiveThread,
  messages,
  setMessages,
  newMessage,
  setNewMessage,
  loading,
  isSending,
  typingStatus,
  fetchMessages,
  handleSendMessage,
  userRole,
  isUnauthorized
}) {
  const { socket } = useSocket();
  const messagesEndRef = useRef(null);
  const [threadSearch, setThreadSearch] = useState("");
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef(null);

  const Layout = userRole === 'admin' ? AdminLayout : (userRole === 'seller' ? SellerLayout : CustomerLayout);

  const filteredThreads = threads.filter((thread) => {
    const q = threadSearch.trim().toLowerCase();
    if (!q) return true;

    const name = (thread.otherUser?.name || "").toLowerCase();
    const last = (thread.lastMessage || "").toLowerCase();
    return name.includes(q) || last.includes(q);
  });

  const sidebarThreads = filteredThreads.length > 0
    ? filteredThreads
    : activeThread
      ? [activeThread]
      : [];

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  useEffect(() => {
    const handleClickOutside = (event) => {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        setMenuOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  const handleMarkAllRead = async () => {
    if (!activeThread) return;
    setMenuOpen(false);
    try {
      await api.put(`/chat/read/${activeThread.otherUser.id}`);
      setMessages((currentMessages) => currentMessages);
      setActiveThread((currentThread) => currentThread ? { ...currentThread, unreadCount: 0 } : currentThread);
    } catch (error) {
      console.warn("Mark read failed", error?.response?.data || error.message);
    }
  };

  const handleDeleteConversation = async () => {
    if (!activeThread) return;
    if (!window.confirm(`Delete all messages with ${activeThread.otherUser?.name || "this artisan"}? This cannot be undone.`)) return;
    setMenuOpen(false);
    try {
      await api.delete(`/chat/conversation/${activeThread.otherUser.id}`);
      setMessages([]);
      setActiveThread(null);
    } catch (error) {
      console.warn("Delete conversation failed", error?.response?.data || error.message);
    }
  };

  if (isUnauthorized) {
    return (
      <Layout>
        <div className="max-w-7xl mx-auto h-[calc(100vh-140px)] flex flex-col items-center justify-center text-center px-4">
          <div className="artisan-card max-w-md p-10 flex flex-col items-center space-y-8 animate-fade-up">
            <div className="w-20 h-20 bg-(--cream) rounded-full flex items-center justify-center">
              <MessageCircle className="w-10 h-10 text-(--rust)" />
            </div>
            <div>
              <div className="eyebrow mb-2">Heritage Interaction</div>
              <h2 className="font-serif text-3xl font-bold text-(--charcoal) mb-4">Login Required</h2>
              <p className="text-sm text-(--muted) leading-relaxed italic">
                You must be authenticated to access your secure messages and collaborate with our master artisans.
              </p>
            </div>
            <button
              onClick={() => window.location.href = "/login"}
              className="w-full py-4 bg-(--bark) text-white font-bold uppercase tracking-[0.2em] rounded-xl shadow-lg hover:bg-(--rust) transition-all active:scale-95 flex items-center justify-center gap-3 group"
            >
              Sign In to Continue <Send className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
            </button>
            <button
              onClick={() => window.location.href = "/"}
              className="text-xs font-bold text-(--muted) uppercase tracking-widest hover:text-(--rust) transition-colors"
            >
              Return to Showcase
            </button>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      {/* Messaging Wrapper */}
      <div className="lg:max-w-7xl lg:mx-auto lg:h-[calc(100vh-140px)] lg:rounded-3xl lg:overflow-hidden lg:shadow-2xl lg:border lg:border-(--border)
                      max-lg:fixed max-lg:top-[72px] max-lg:bottom-[calc(80px+var(--safe-bottom,0px))] max-lg:left-0 max-lg:right-0 
                      flex flex-col lg:grid lg:grid-cols-[384px_minmax(0,1fr)] bg-white min-h-0 z-10">
        {/* Thread List */}
        <aside className={`w-full lg:w-auto border-r border-(--border) flex flex-col bg-white min-h-0 ${activeThread ? 'hidden lg:flex' : 'flex'}`}>
          <div className="p-4 lg:p-6 border-b border-(--border)">
            <div className="text-[10px] uppercase tracking-wider text-[#999] font-bold mb-1">Interface Support</div>
            <h1 className="text-lg lg:text-xl font-medium text-[#111] mb-3">
              Customer <span className="text-[#C0402A] italic">inquiries</span>
            </h1>
            <div className="relative">
              <div className="absolute left-3 top-1/2 -translate-y-1/2 text-[#aaa]">
                <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><circle cx="6.5" cy="6.5" r="5" stroke="currentColor" strokeWidth="1.5"/><path d="M10.5 10.5L14 14" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round"/></svg>
              </div>
              <input
                type="text"
                value={threadSearch}
                onChange={(e) => setThreadSearch(e.target.value)}
                placeholder="Filter inquiries..."
                className="w-full pl-9 pr-4 py-2 bg-[#f5f5f5] border border-[#ddd] rounded-lg outline-none focus:border-[#C0402A] transition-all text-sm text-[#333] placeholder-[#aaa]"
              />
            </div>
          </div>

          <div className={`flex-1 min-h-0 custom-scrollbar px-2 py-4 space-y-1 overscroll-contain ${activeThread ? 'lg:overflow-hidden' : 'overflow-y-scroll'}`}>
            {loading ? (
              <div className="py-10 text-center text-(--muted) animate-pulse italic text-sm">Synchronizing threads...</div>
            ) : sidebarThreads.length === 0 ? (
              <div className="py-10 text-center text-(--muted) text-sm italic">
                {threadSearch ? "No matching conversations" : "No active conversations found"}
              </div>
            ) : (
              sidebarThreads.map((thread) => {
                const nameChar = thread.otherUser?.name?.[0] || 'A';
                const avatarBg = thread.otherUser?.role === 'seller' ? '#FAD4C0' : '#D4EFDE';
                const avatarColor = thread.otherUser?.role === 'seller' ? '#7A2A10' : '#27500A';
                
                return (
                <button
                  key={thread.otherUser?.id || thread.otherUser?.name || thread.timestamp || "current-thread"}
                  onClick={() => fetchMessages(thread)}
                  className={`w-full p-2.5 rounded-lg flex items-center gap-3 transition-all duration-300 text-left group ${
                    activeThread?.otherUser?.id === thread.otherUser?.id
                      ? "bg-[#f5f5f5] ring-1 ring-[#ddd]"
                      : "hover:bg-[#f9f9f9]"
                  }`}
                >
                  <div className="w-8 h-8 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0" style={{ backgroundColor: avatarBg, color: avatarColor }}>
                    {nameChar}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex justify-between items-center">
                      <div className="flex items-center gap-1.5 min-w-0">
                        <span className="font-bold text-xs truncate text-[#111]">{thread.otherUser?.name || 'Artisan'}</span>
                        {(thread.unreadCount > 0 || thread.isGhost) && <span className="bg-[#C0402A] text-white text-[8px] font-bold px-1.5 py-0.5 rounded-full uppercase">new</span>}
                      </div>
                      <span className="text-[9px] font-bold text-[#999] whitespace-nowrap ml-2">
                        {thread.isGhost ? "New" : thread.timestamp ? new Date(thread.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : "2:09 PM"}
                      </span>
                    </div>
                    <p className="text-[11px] text-[#888] truncate mt-0.5">
                      {thread.isGhost ? "Start a new conversation" : (thread.lastMessage || "Click to view messages")}
                    </p>
                  </div>
                </button>
              )})
            )}
          </div>
        </aside>

        {/* Chat Area */}
        <section className={`flex-1 flex flex-col bg-white min-h-0 ${!activeThread ? 'hidden lg:flex' : 'flex'}`}>
          {activeThread ? (
            <div className="flex-1 flex flex-col bg-white min-h-0">
              <div className="p-4 lg:p-6 border-b border-(--border) flex items-center justify-between bg-white z-10">
                <div className="flex items-center gap-3">
                  <button
                    onClick={() => setActiveThread(null)}
                    className="p-2 -ml-2 text-[#999] hover:text-[#C0402A] transition-colors"
                  >
                    <ChevronLeft className="w-6 h-6" />
                  </button>
 
                  <div className="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-md bg-[#FAD4C0] !text-[#7A2A10]">
                    {activeThread.otherUser?.name?.[0] || "?"}
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="text-sm font-bold text-[#111] flex items-center gap-2 leading-none">
                        {activeThread.otherUser?.name || 'Artisan'}
                        {(activeThread.unreadCount > 0 || activeThread.isGhost) && <span className="bg-[#C0402A] text-white text-[9px] px-2 py-0.5 rounded-full uppercase leading-tight font-bold">new</span>}
                        <span className="w-2 h-2 rounded-full bg-[#2DA06C] border border-white shadow-sm mt-0.5"></span>
                      </h2>
                    </div>
                    <div className="text-[10px] text-[#aaa] font-medium lowercase mt-0.5">
                      {typingStatus.isTyping ? "Typing..." : "2 min ago"}
                    </div>
                  </div>
                </div>
                <div className="relative" ref={menuRef}>
                  <button
                    onClick={() => setMenuOpen((current) => !current)}
                    className={`p-3 rounded-xl transition-all ${menuOpen ? 'bg-(--rust) text-white' : 'hover:bg-(--cream) text-(--muted) hover:text-(--rust)'}`}
                  >
                    <MoreVertical className="w-5 h-5" />
                  </button>

                  {menuOpen && (
                    <div className="absolute right-0 top-[calc(100%+8px)] z-50 w-56 bg-white rounded-2xl shadow-2xl border border-(--border) overflow-hidden animate-fade-up">
                      <button
                        onClick={() => {
                          setMenuOpen(false);
                          window.location.href = `/shop?id=${activeThread.otherUser?.id}`;
                        }}
                        className="w-full flex items-center gap-3 px-5 py-4 text-sm font-semibold text-(--charcoal) hover:bg-(--cream) transition-colors text-left"
                      >
                        <span className="w-7 h-7 rounded-lg bg-(--cream) flex items-center justify-center text-(--rust)"><Store className="w-4 h-4" /></span>
                        View Shop
                      </button>

                      <button
                        onClick={handleMarkAllRead}
                        className="w-full flex items-center gap-3 px-5 py-4 text-sm font-semibold text-(--charcoal) hover:bg-(--cream) transition-colors text-left"
                      >
                        <span className="w-7 h-7 rounded-lg bg-(--cream) flex items-center justify-center text-(--rust)">✓</span>
                        Mark All as Read
                      </button>

                      <div className="h-px bg-(--border) mx-4" />

                      <button
                        onClick={handleDeleteConversation}
                        className="w-full flex items-center gap-3 px-5 py-4 text-sm font-semibold text-red-500 hover:bg-red-50 transition-colors text-left"
                      >
                        <span className="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center">🗑</span>
                        Delete Conversation
                      </button>
                    </div>
                  )}
                </div>
              </div>

              <div className="flex-1 overflow-y-auto p-4 lg:p-6 space-y-4 bg-white custom-scrollbar min-h-0 overscroll-contain">
                {activeThread?.productContext && (
                  <div className="mx-0 bg-white border border-(--border) rounded-2xl p-4 flex items-center gap-4 shadow-sm animate-pulse-slow">
                    <div className="w-16 h-16 relative bg-(--cream) rounded-xl overflow-hidden shrink-0 border border-(--border)">
                      <Image src={resolveBackendImageUrl(activeThread.productContext.image, "/images/placeholder.png")} alt="Inquiry product" fill className="object-cover" unoptimized />
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="text-[10px] font-bold text-(--rust) uppercase tracking-widest mb-1">Inquiring About</div>
                      <h3 className="text-sm font-bold text-(--charcoal) truncate">{activeThread.productContext.name}</h3>
                      <div className="text-xs font-serif font-bold text-(--rust) mt-0.5">₱{Number(activeThread.productContext.price).toLocaleString()}</div>
                    </div>
                    <button
                      onClick={() => window.location.href = `/product?id=${activeThread.productContext.id}`}
                      className="px-4 py-2 bg-(--bark) text-white text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-(--rust) transition-all whitespace-nowrap"
                    >
                      Details
                    </button>
                  </div>
                )}

                <div className="space-y-2">
                  {messages.map((msg, i) => {
                    const customerData = getStoredUserForRole("customer");
                    const storedUser = customerData || {};

                    const msgSenderId = msg.senderId || msg.sender?.id;
                    const isMe = String(msgSenderId) === String(storedUser.id);
                    
                    // Force heritage inquiry text to look like 'incoming' to match user's reference image
                    const isHeritageInquiry = msg.content?.includes("I am inquiring about this masterpiece");
                    const isIncoming = !isMe || isHeritageInquiry;

                    return (
                      <motion.div
                        key={i}
                        initial={{ opacity: 0, y: 5 }}
                        animate={{ opacity: 1, y: 0 }}
                        className={`flex ${!isIncoming ? 'justify-end' : 'justify-start'} mb-2`}
                      >
                        <div className={`max-w-[80%] p-3.5 rounded-2xl text-xs leading-relaxed shadow-sm ${
                          !isIncoming 
                          ? 'bg-[#C0402A] text-white rounded-tr-none' 
                          : 'bg-[#f2f2f2] text-[#111] rounded-tl-none'
                        }`}>
                          {msg.content}
                          <div className={`text-[10px] mt-2 opacity-65 font-medium ${!isIncoming ? 'text-right' : 'text-left'}`}>
                            {msg.createdAt
                              ? new Date(msg.createdAt).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
                              : "2:14 PM"}
                          </div>
                        </div>
                      </motion.div>
                    );
                  })}
                </div>
                <div ref={messagesEndRef} />
              </div>

              <form onSubmit={handleSendMessage} className="p-3 lg:p-4 border-t border-[#ddd] bg-white">
                <div className="flex items-center gap-2">
                  <input
                    type="text"
                    value={newMessage}
                    onChange={(e) => {
                      setNewMessage(e.target.value);
                      if (socket && activeThread) {
                        socket.emit("typing", { receiverId: activeThread.otherUser?.id, isTyping: true });
                      }
                    }}
                    placeholder={activeThread.isGhost ? `Start a conversation...` : "Type your heritage response..."}
                    className="flex-1 bg-[#f5f5f5] border border-[#ddd] outline-none rounded-full px-4 py-2 text-xs text-[#111]"
                  />
                  <button type="submit" disabled={isSending} className="w-8 h-8 bg-[#C0402A] text-white rounded-full flex items-center justify-center hover:opacity-90 transition-all disabled:opacity-50 shrink-0 shadow-md">
                    {isSending ? <Loader2 className="w-3 h-3 animate-spin" /> : <svg width="14" height="14" fill="white" viewBox="0 0 16 16"><path d="M2 8L14 2L10 8L14 14L2 8Z"/></svg>}
                  </button>
                </div>
              </form>
            </div>
          ) : (
            <div className="flex-1 flex flex-col items-center justify-center text-center space-y-6 p-10 min-h-0">
              <div className="w-24 h-24 bg-(--cream) rounded-3xl flex items-center justify-center shadow-inner ring-1 ring-(--border)">
                <MessageCircle className="w-10 h-10 text-(--muted) opacity-30" />
              </div>
              <div>
                <h3 className="font-serif text-lg font-bold opacity-60">Workshop Communication</h3>
                <p className="max-w-xs text-(--muted) text-xs font-medium leading-relaxed italic mt-1.5">
                  Select an artisan thread on the left to begin your mastercraft dialogue.
                </p>
              </div>
            </div>
          )}
        </section>
      </div>
    </Layout>
  );
}
