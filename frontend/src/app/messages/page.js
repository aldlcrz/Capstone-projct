"use client";
import React, { useState, useEffect, useRef } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { MessageCircle, Search, Store, Send, Loader2 } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { useSearchParams } from "next/navigation";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

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
          setActiveThread(allThreads[0]);
          fetchMessages(allThreads[0]);
        }

        setThreads(allThreads);
      } catch (err) {
        console.error("Failed to fetch threads");
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
        api.get("/chat/threads").then(res => setThreads(res.data)).catch(() => {});
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
  }, [socket, sellerIdParam]);

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

      setMessages(prev => [...prev, res.data]);
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
      messages={messages}
      setMessages={setMessages}
      newMessage={newMessage}
      setNewMessage={setNewMessage}
      loading={loading}
      isSending={isSending}
      typingStatus={typingStatus}
      fetchMessages={fetchMessages}
      handleSendMessage={handleSendMessage}
    />
  );
}

function MessagesUI({
  threads,
  activeThread,
  messages,
  setMessages,
  newMessage,
  setNewMessage,
  loading,
  isSending,
  typingStatus,
  fetchMessages,
  handleSendMessage
}) {
  const { socket } = useSocket();
  const messagesEndRef = useRef(null);

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  return (
    <CustomerLayout>
      <div className="max-w-7xl mx-auto h-[calc(100vh-140px)] flex flex-col md:flex-row gap-8">
        {/* Thread List */}
        <div className="w-full md:w-96 flex flex-col gap-6">
          <div>
            <div className="eyebrow">Communication</div>
            <h1 className="font-serif text-3xl font-bold tracking-tight text-[var(--charcoal)]">
              Heritage <span className="text-[var(--rust)] italic lowercase">Inquiries</span>
            </h1>
          </div>

          <div className="artisan-card p-0 flex flex-col h-full overflow-hidden">
            <div className="p-4 border-b border-[var(--border)] bg-[var(--input-bg)]">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
                <input
                  type="text"
                  placeholder="Search artisans..."
                  className="w-full pl-10 pr-4 py-2 text-sm bg-white border border-[var(--border)] rounded-xl outline-none focus:border-[var(--rust)]"
                />
              </div>
            </div>

            <div className="flex-1 overflow-y-auto custom-scrollbar">
              {loading ? (
                <div className="p-10 text-center text-xs text-[var(--muted)] animate-pulse">Sychronizing threads...</div>
              ) : threads.length === 0 ? (
                <div className="p-10 text-center text-[var(--muted)] text-sm italic">No active conversations found</div>
              ) : (
                threads.map((thread) => (
                  <button
                    key={thread.otherUser?.id || thread.otherUser?.id}
                    onClick={() => fetchMessages(thread)}
                    className={`w-full p-6 flex items-start gap-4 text-left border-b border-[var(--border)] transition-all hover:bg-[var(--cream)] ${activeThread?.otherUser?.id === thread.otherUser?.id ? 'bg-[rgba(192,66,42,0.05)] border-l-4 border-l-[var(--rust)] shadow-sm' : ''}`}
                  >
                    <div className="w-12 h-12 bg-[var(--bark)] rounded-2xl flex items-center justify-center text-white font-serif text-lg font-bold">
                      {thread.otherUser?.name?.[0] || 'A'}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex justify-between items-center mb-1">
                        <div className="flex items-center gap-2">
                          <span className="font-bold text-sm truncate">{thread.otherUser?.name || 'Artisan'}</span>
                          <span className={`text-[8px] px-1.5 py-0.5 rounded font-bold uppercase tracking-tight shadow-sm ${thread.otherUser?.role === 'seller' ? 'bg-[var(--rust)] text-white' : 'bg-[var(--bark)] text-white'}`}>
                            {thread.otherUser?.role || 'User'}
                          </span>
                        </div>
                        <span className="text-[10px] text-[var(--muted)] opacity-60 ml-2">
                          {thread.isGhost ? "New" : thread.timestamp ? new Date(thread.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : "Active"}
                        </span>
                      </div>
                      <p className="text-xs text-[var(--muted)] line-clamp-1 italic tracking-tight">
                        {thread.isGhost ? "Start a new conversation" : (thread.lastMessage || "Click to view messages")}
                      </p>
                      {thread.unreadCount > 0 && (
                        <div className="mt-1 inline-flex h-4 min-w-4 px-1 items-center justify-center bg-[var(--rust)] text-white text-[8px] font-bold rounded-full">
                          {thread.unreadCount}
                        </div>
                      )}
                    </div>
                  </button>
                ))
              )}
            </div>
          </div>
        </div>

        {/* Chat Area */}
        <div className="flex-1 flex flex-col h-full overflow-hidden">
          {activeThread ? (
            <div className="artisan-card p-0 flex flex-col h-full shadow-2xl overflow-hidden animate-fade-up">
              <div className="p-6 border-b border-[var(--border)] flex items-center justify-between bg-white z-10">
                <div className="flex items-center gap-4">
                  <div className="w-10 h-10 bg-[var(--bark)] rounded-xl flex items-center justify-center text-white font-serif text-base font-bold">
                    {activeThread.otherUser?.name?.[0] || 'A'}
                  </div>
                  <div>
                    <div className="flex items-center gap-2">
                      <h2 className="text-base font-bold text-[var(--charcoal)]">{activeThread.otherUser?.name || 'Artisan'}</h2>
                      <span className={`text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-widest ${activeThread.otherUser?.role === 'seller' ? 'bg-[var(--rust)] text-white' : 'bg-[var(--bark)] text-white'}`}>
                        {activeThread.otherUser?.role}
                      </span>
                    </div>
                    <div className="text-[10px] text-green-600 font-bold uppercase tracking-widest flex items-center gap-1.5 mt-1">
                      {typingStatus.isTyping ? (
                        <span className="text-[var(--rust)] animate-pulse">TYPING...</span>
                      ) : (
                        <>
                          <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" /> ONLINE
                        </>
                      )}
                    </div>
                  </div>
                </div>
                <button
                  onClick={() => window.location.href = `/shop?id=${activeThread.otherUser?.id}`}
                  className="flex items-center gap-2 px-4 py-2 border border-[var(--border)] rounded-xl text-[10px] font-bold uppercase tracking-widest hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all"
                >
                  <Store className="w-3.5 h-3.5" /> View Shop
                </button>
              </div>

              <div className="flex-1 p-8 overflow-y-auto custom-scrollbar space-y-8 bg-[#FDFBF9]">
                {activeThread?.productContext && (
                  <div className="mx-8 mt-4 mb-8 bg-white border border-[var(--border)] rounded-2xl p-4 flex items-center gap-4 shadow-sm animate-pulse-slow">
                    <div className="w-16 h-16 relative bg-[var(--cream)] rounded-xl overflow-hidden shrink-0 border border-[var(--border)]">
                      <img src={activeThread.productContext.image} alt="Inquiry product" className="object-cover w-full h-full" />
                    </div>
                    <div className="flex-1">
                      <div className="text-[10px] font-bold text-[var(--rust)] uppercase tracking-widest mb-1">Inquiring About</div>
                      <h3 className="text-sm font-bold text-[var(--charcoal)] truncate max-w-[300px]">{activeThread.productContext.name}</h3>
                      <div className="text-xs font-serif font-bold text-[var(--rust)] mt-0.5">₱{Number(activeThread.productContext.price).toLocaleString()}</div>
                    </div>
                    <button 
                      onClick={() => window.location.href = `/product?id=${activeThread.productContext.id}`}
                      className="px-4 py-2 bg-[var(--bark)] text-white text-[10px] font-bold uppercase tracking-widest rounded-xl hover:bg-[var(--rust)] transition-all"
                    >
                      View Details
                    </button>
                  </div>
                )}

                <div className="text-center py-4 border-b border-[var(--border)] mb-4">
                  <span className="bg-white px-4 py-1.5 rounded-full border border-[var(--border)] text-[8px] font-bold uppercase tracking-widest text-[var(--muted)]">Secure Heritage Thread Opened</span>
                </div>

                {messages.map((msg, i) => {
                  const storedUser = JSON.parse(localStorage.getItem("user") || "{}");
                  const msgSenderId = msg.senderId || msg.sender?.id;
                  const isMe = String(msgSenderId) === String(storedUser.id);
                  const senderName = msg.sender?.name || activeThread.otherUser?.name || 'User';

                  return (
                    <motion.div
                      key={i}
                      initial={{ opacity: 0, x: isMe ? 10 : -10 }}
                      animate={{ opacity: 1, x: 0 }}
                      className={`flex ${isMe ? 'justify-end' : 'items-end'} gap-3 mb-6`}
                    >
                      {!isMe && (
                        <div title={senderName} className="w-8 h-8 rounded-full bg-[var(--bark)] text-white flex items-center justify-center text-[10px] font-bold shrink-0 shadow-sm">
                          {senderName[0]}
                        </div>
                      )}
                      
                      <div className={`flex flex-col ${isMe ? 'items-end' : 'items-start'} max-w-[70%]`}>
                        <div className={`p-5 rounded-2xl shadow-sm border border-[var(--border)] ${isMe ? 'bg-[var(--rust)] text-white rounded-tr-none border-transparent' : 'bg-white text-[var(--charcoal)] rounded-tl-none'}`}>
                          <div className="text-sm font-sans leading-relaxed">{msg.content}</div>
                          <div className={`mt-2 text-[8px] font-bold uppercase tracking-widest ${isMe ? 'text-white/40' : 'text-[var(--muted)]'}`}>
                            {msg.createdAt ? new Date(msg.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : "Just now"}
                          </div>
                        </div>
                      </div>

                      {isMe && (
                        <div className="w-8 h-8 rounded-full bg-[var(--rust)] text-white flex items-center justify-center text-[10px] font-bold shrink-0 shadow-md">
                          {storedUser.name?.[0] || 'M'}
                        </div>
                      )}
                    </motion.div>
                  );
                })}
                <div ref={messagesEndRef} />
              </div>

              <form onSubmit={handleSendMessage} className="p-6 border-t border-[var(--border)] bg-gray-50 flex gap-4">
                <input
                  type="text"
                  value={newMessage}
                  onChange={(e) => {
                    setNewMessage(e.target.value);
                    if (socket && activeThread) {
                      socket.emit("typing", { receiverId: activeThread.otherUser?.id, isTyping: true });
                    }
                  }}
                  placeholder="Type your message to the artisan..."
                  className="flex-1 px-5 py-4 bg-white border border-[var(--border)] rounded-2xl focus:outline-none focus:border-[var(--rust)] transition-all font-sans text-sm"
                />
                <button type="submit" disabled={isSending} className="p-4 bg-[var(--bark)] text-white rounded-2xl hover:bg-[var(--rust)] transition-all shadow-lg active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                  {isSending ? <Loader2 className="w-6 h-6 animate-spin" /> : <Send className="w-6 h-6" />}
                </button>
              </form>
            </div>
          ) : (
            <div className="artisan-card h-full flex flex-col items-center justify-center text-center space-y-6 opacity-40">
              <div className="w-20 h-20 bg-[var(--cream)] rounded-full flex items-center justify-center">
                <MessageCircle className="w-10 h-10 text-[var(--muted)]" />
              </div>
              <div>
                <h3 className="text-2xl font-bold font-serif">Open your Conversations</h3>
                <p className="text-sm">Select an artisan on the left to start your heritage journey</p>
              </div>
            </div>
          )}
        </div>
      </div>
    </CustomerLayout>
  );
}
