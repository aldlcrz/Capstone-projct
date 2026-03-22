"use client";
import React, { useState, useEffect, useRef } from "react";
import SellerLayout from "@/components/SellerLayout";
import { MessageCircle, Search, MoreVertical, Send, User, ShieldCheck } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function SellerMessages() {
  const [threads, setThreads] = useState([]);
  const [activeThread, setActiveThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const { socket } = useSocket();
  const scrollRef = useRef(null);

  useEffect(() => {
    const fetchThreads = async () => {
      try {
        const res = await api.get("/chat/threads", {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
        });
        setThreads(res.data);
      } catch (err) {
        console.error("Failed to fetch threads");
      } finally {
        setLoading(false);
      }
    };
    fetchThreads();

    if (socket) {
        socket.on('receive_message', (msg) => {
            if (activeThread && msg.threadId === activeThread.id) {
                setMessages(prev => [...prev, msg]);
            }
            fetchThreads();
        });
    }

    return () => {
        if (socket) {
            socket.off('receive_message');
        }
    };
  }, [activeThread, socket]);

  const fetchMessages = async (thread) => {
    setActiveThread(thread);
    try {
      const res = await api.get(`/chat/messages/${thread.id}`);
      setMessages(res.data);
    } catch (err) {
       console.error("Failed to fetch messages");
    }
  };

  const sendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !activeThread) return;
    
    const user = JSON.parse(localStorage.getItem("user") || "{}");
    const msgData = {
        threadId: activeThread.id,
        senderId: user.id,
        receiverId: user.id === activeThread.buyerId ? activeThread.sellerId : activeThread.buyerId,
        content: newMessage
    };

    try {
        const res = await api.post("/chat/send", msgData, {
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
        });
        if (socket) {
            socket.emit('send_message', res.data);
        }
        setMessages(prev => [...prev, res.data]);
        setNewMessage("");
    } catch (err) {
        console.error("Failed to send message");
    }
  };

  useEffect(() => {
    scrollRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  return (
    <SellerLayout>
      <div className="h-[calc(100vh-140px)] flex bg-white rounded-3xl overflow-hidden shadow-2xl border border-[var(--border)]">
        {/* Left: Thread List */}
        <div className="w-96 border-r border-[var(--border)] flex flex-col bg-gray-50/50">
          <div className="p-8 border-b border-[var(--border)]">
              <div className="eyebrow !mb-2">Heritage Support</div>
              <h2 className="font-serif text-2xl font-bold text-[var(--charcoal)] mb-6">Patron <span className="text-[var(--rust)] italic lowercase">Inquiries</span></h2>
              <div className="relative">
                <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
                <input 
                  type="text" 
                  placeholder="Filter inquiries..." 
                  className="w-full pl-12 pr-4 py-3 bg-white border border-[var(--border)] rounded-2xl outline-none focus:border-[var(--rust)] transition-all text-xs font-bold uppercase tracking-widest shadow-sm"
                />
              </div>
          </div>
          <div className="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-4">
            {loading ? (
                 <div className="py-10 text-center text-[var(--muted)] animate-pulse italic">Synchronizing workshop threads...</div>
            ) : threads.length === 0 ? (
                 <div className="py-10 text-center text-[var(--muted)] space-y-4">
                    <MessageCircle className="w-10 h-10 mx-auto opacity-20" />
                    <div className="text-sm font-bold opacity-60">NO INQUIRIES YET</div>
                 </div>
            ) : threads.map((thread) => (
              <button 
                key={thread.id}
                onClick={() => fetchMessages(thread)}
                className={`w-full p-5 rounded-2xl flex items-start gap-4 transition-all duration-300 ${activeThread?.id === thread.id ? 'bg-white shadow-xl ring-2 ring-[var(--rust)] scale-[1.02]' : 'hover:bg-white hover:shadow-lg'}`}
              >
                <div className="w-12 h-12 bg-[var(--bark)] rounded-xl flex items-center justify-center text-white font-serif text-lg font-bold shadow-md shrink-0">
                  {thread?.buyer?.name?.[0]}
                </div>
                <div className="flex-1 text-left">
                  <div className="flex justify-between items-center mb-1">
                    <div className="text-sm font-bold text-[var(--charcoal)] truncate w-32">{thread?.buyer?.name}</div>
                    <div className="text-[10px] font-bold text-[var(--muted)]">12:30 PM</div>
                  </div>
                  <div className="text-xs text-[var(--muted)] line-clamp-1 opacity-70 italic">"{thread.lastMessage || 'Starting inquiry...'}"</div>
                </div>
              </button>
            ))}
          </div>
        </div>

        {/* Right: Active Chat */}
        <div className="flex-1 flex flex-col bg-white relative">
          {activeThread ? (
            <>
              <div className="p-8 border-b border-[var(--border)] flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-[var(--bark)] rounded-xl flex items-center justify-center text-white font-serif text-xl font-bold shadow-lg">
                    {activeThread?.buyer?.name?.[0]}
                  </div>
                  <div>
                    <div className="text-lg font-bold text-[var(--charcoal)]">{activeThread?.buyer?.name}</div>
                    <div className="text-[10px] text-green-600 font-bold uppercase tracking-widest flex items-center gap-1.5"><span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" /> Active Session</div>
                  </div>
                </div>
                <button className="p-3 hover:bg-[var(--cream)] rounded-xl transition-all text-[var(--muted)] hover:text-[var(--rust)]"><MoreVertical className="w-5 h-5" /></button>
              </div>

              <div className="flex-1 overflow-y-auto p-10 space-y-8 bg-[var(--cream)]/30 custom-scrollbar">
                {messages.map((msg, i) => {
                  const isMine = msg.senderId === JSON.parse(localStorage.getItem("user"))?.id;
                  return (
                    <motion.div 
                      key={i}
                      initial={{ opacity: 0, y: 10, scale: 0.95 }}
                      animate={{ opacity: 1, y: 0, scale: 1 }}
                      className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}
                    >
                      <div className={`max-w-md p-6 rounded-3xl shadow-xl font-medium text-sm leading-relaxed ${isMine ? 'bg-[var(--rust)] text-white rounded-tr-none border-4 border-white' : 'bg-white text-[var(--charcoal)] rounded-tl-none border-4 border-white'}`}>
                        {msg.content}
                        <div className={`text-[9px] mt-2 font-bold opacity-60 ${isMine ? 'text-white/80' : 'text-[var(--muted)]'}`}> SENT AT 12:35 PM </div>
                      </div>
                    </motion.div>
                  );
                })}
                <div ref={scrollRef} />
              </div>

              <form onSubmit={sendMessage} className="p-8 border-t border-[var(--border)] bg-white">
                <div className="flex items-center gap-4 bg-[var(--input-bg)] p-3 pl-6 rounded-2xl border border-[var(--border)] focus-within:border-[var(--rust)] focus-within:bg-white transition-all shadow-inner group">
                  <textarea 
                    value={newMessage}
                    onChange={(e) => setNewMessage(e.target.value)}
                    placeholder="Type your heritage response..." 
                    rows={1}
                    className="flex-1 bg-transparent border-none outline-none text-sm font-medium italic resize-none py-2"
                  />
                  <button 
                    type="submit"
                    className="p-4 bg-[var(--rust)] text-white rounded-xl shadow-xl hover:scale-105 transition-all group-focus-within:rotate-3"
                  >
                    <Send className="w-5 h-5" />
                  </button>
                </div>
              </form>
            </>
          ) : (
            <div className="flex-1 flex flex-col items-center justify-center text-center space-y-6">
              <div className="w-24 h-24 bg-[var(--cream)] rounded-3xl flex items-center justify-center shadow-inner scale-110 mb-4 ring-1 ring-[var(--border)]">
                <MessageCircle className="w-10 h-10 text-[var(--muted)] opacity-30" />
              </div>
              <h3 className="font-serif text-3xl font-bold opacity-60">Workshop Communication</h3>
              <p className="max-w-xs text-[var(--muted)] font-medium leading-relaxed italic">Select a patron thread to begin sharing the details of your mastercraft process.</p>
            </div>
          )}
        </div>
      </div>
    </SellerLayout>
  );
}
