"use client";
import React, { useState, useEffect } from "react";
import CustomerLayout from "@/components/CustomerLayout";
import { MessageCircle, Search, Store, Send, User, ChevronRight } from "lucide-react";
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

  const [threads, setThreads] = useState([]);
  const [activeThread, setActiveThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [typingStatus, setTypingStatus] = useState({ isTyping: false, senderId: null });

  const { socket } = useSocket();

  useEffect(() => {
    const fetchThreads = async () => {
      try {
        const res = await api.get("/chat/threads");
        let allThreads = res.data;

        // Handle incoming sellerId from inquiry
        if (sellerIdParam) {
          const existingThread = allThreads.find(t => String(t.id) === String(sellerIdParam));
          if (existingThread) {
            setActiveThread(existingThread);
            fetchMessages(existingThread);
          } else {
            // Create a "ghost" thread for new inquiry
            const ghostThread = { 
              id: sellerIdParam, 
              name: sellerNameParam || "Artisan Workshop", 
              role: "seller",
              isGhost: true 
            };
            setActiveThread(ghostThread);
            setMessages([]);
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
      socket.on("receive_message", (msg) => {
        if (activeThread && (String(msg.senderId) === String(activeThread.id) || String(msg.recipientId) === String(activeThread.id))) {
          setMessages(prev => [...prev, msg]);
        }
      });

      socket.on("typing_status", (data) => {
        if (activeThread && String(data.senderId) === String(activeThread.id)) {
          setTypingStatus(data);
          setTimeout(() => setTypingStatus({ isTyping: false, senderId: null }), 3000);
        }
      });
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
      const res = await api.get(`/chat/${thread.id}`);
      setMessages(res.data);
    } catch (err) {
      console.error("Failed to fetch messages");
    }
  };

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !activeThread) return;

    try {
      const res = await api.post("/chat", {
        recipientId: activeThread.id,
        content: newMessage
      }, {
        headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
      });
      
      if (activeThread.isGhost) {
        const realThread = { ...activeThread, isGhost: false };
        setThreads(prev => [realThread, ...prev.filter(t => t.id !== activeThread.id)]);
        setActiveThread(realThread);
      }

      setMessages(prev => [...prev, res.data]);
      setNewMessage("");
    } catch (err) {
      console.error("Failed to send message");
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
  typingStatus, 
  fetchMessages, 
  handleSendMessage 
}) {
    const { socket } = useSocket();
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
                    key={thread.id}
                    onClick={() => fetchMessages(thread)}
                    className={`w-full p-6 flex items-start gap-4 text-left border-b border-[var(--border)] transition-all hover:bg-[var(--cream)] ${activeThread?.id === thread.id ? 'bg-[rgba(192,66,42,0.05)] border-l-4 border-l-[var(--rust)] shadow-sm' : ''}`}
                  >
                    <div className="w-12 h-12 bg-[var(--bark)] rounded-2xl flex items-center justify-center text-white font-serif text-lg font-bold">
                      {thread.name?.[0] || 'A'}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="flex justify-between items-center mb-1">
                        <span className="font-bold text-sm truncate">{thread.name}</span>
                        <span className="text-[10px] text-[var(--muted)] opacity-60">{thread.isGhost ? "New" : "Active"}</span>
                      </div>
                      <p className="text-xs text-[var(--muted)] line-clamp-1 italic tracking-tight">{thread.isGhost ? "Start a new conversation" : "Active thread - click to view messages"}</p>
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
                    {activeThread.name?.[0] || 'A'}
                  </div>
                  <div>
                    <h2 className="text-base font-bold text-[var(--charcoal)]">{activeThread.name}</h2>
                    <div className="text-[10px] text-green-600 font-bold uppercase tracking-widest flex items-center gap-1.5">
                      {typingStatus.isTyping ? (
                        <span className="text-[var(--rust)] animate-pulse">ARTISAN IS TYPING...</span>
                      ) : (
                        <>
                          <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" /> VERIFIED ARTISAN
                        </>
                      )}
                    </div>
                  </div>
                </div>
                <button 
                   onClick={() => window.location.href = `/shop/${activeThread.id}`}
                   className="flex items-center gap-2 px-4 py-2 border border-[var(--border)] rounded-xl text-[10px] font-bold uppercase tracking-widest hover:border-[var(--rust)] hover:text-[var(--rust)] transition-all"
                >
                  <Store className="w-3.5 h-3.5" /> View Shop
                </button>
              </div>

              <div className="flex-1 p-8 overflow-y-auto custom-scrollbar space-y-8 bg-[#FDFBF9]">
                <div className="text-center py-4 border-b border-[var(--border)] mb-4">
                  <span className="bg-white px-4 py-1.5 rounded-full border border-[var(--border)] text-[8px] font-bold uppercase tracking-widest text-[var(--muted)]">Secure Heritage Thread Opened</span>
                </div>
                
                {messages.map((msg, i) => {
                  const storedUser = JSON.parse(localStorage.getItem("user") || "{}");
                  const isMe = String(msg.senderId) === String(storedUser.id);
                  return (
                    <motion.div 
                      key={i}
                      initial={{ opacity: 0, x: isMe ? 10 : -10 }}
                      animate={{ opacity: 1, x: 0 }}
                      className={`flex ${isMe ? 'justify-end' : 'justify-start'}`}
                    >
                      <div className={`max-w-[70%] p-5 rounded-2xl shadow-sm border border-[var(--border)] ${isMe ? 'bg-[var(--rust)] text-white rounded-br-none border-transparent' : 'bg-white text-[var(--charcoal)] rounded-bl-none'}`}>
                        <div className="text-sm font-sans leading-relaxed">{msg.content}</div>
                        <div className={`mt-2 text-[8px] font-bold uppercase tracking-widest ${isMe ? 'text-white/40' : 'text-[var(--muted)]'}`}>
                          {msg.createdAt ? new Date(msg.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : "Just now"}
                        </div>
                      </div>
                    </motion.div>
                  );
                })}
              </div>

              <form onSubmit={handleSendMessage} className="p-6 border-t border-[var(--border)] bg-gray-50 flex gap-4">
                <input 
                  type="text" 
                  value={newMessage}
                  onChange={(e) => {
                    setNewMessage(e.target.value);
                    if (socket && activeThread) {
                      socket.emit("typing", { receiverId: activeThread.id, isTyping: true });
                    }
                  }}
                  placeholder="Type your message to the artisan..." 
                  className="flex-1 px-5 py-4 bg-white border border-[var(--border)] rounded-2xl focus:outline-none focus:border-[var(--rust)] transition-all font-sans text-sm"
                />
                <button type="submit" className="p-4 bg-[var(--bark)] text-white rounded-2xl hover:bg-[var(--rust)] transition-all shadow-lg active:scale-95">
                  <Send className="w-6 h-6" />
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
