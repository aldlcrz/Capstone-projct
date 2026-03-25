"use client";
import React, { useState, useEffect, useRef } from "react";
import SellerLayout from "@/components/SellerLayout";
import { MessageCircle, Search, MoreVertical, Send, Loader2 } from "lucide-react";
import { motion } from "framer-motion";
import { api } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";

export default function SellerMessages() {
  const [threads, setThreads] = useState([]);
  const [activeThread, setActiveThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [isSending, setIsSending] = useState(false);
  const { socket } = useSocket();
  const scrollRef = useRef(null);

  const fetchThreads = async () => {
    try {
      const res = await api.get("/chat/threads");
      setThreads(res.data);
    } catch (err) {
      console.error("Failed to fetch threads", err?.response?.data || err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchThreads();
  }, []);

  useEffect(() => {
    if (!socket) return;

    const handleNewMessage = (msg) => {
      if (activeThread && (
        String(msg.senderId) === String(activeThread.otherUser.id) ||
        String(msg.receiverId) === String(activeThread.otherUser.id)
      )) {
        setMessages(prev => [...prev, msg]);
      }
      fetchThreads();
    };

    socket.on("receive_message", handleNewMessage);
    return () => socket.off("receive_message", handleNewMessage);
  }, [socket, activeThread]);

  useEffect(() => {
    scrollRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const fetchMessages = async (thread) => {
    setActiveThread(thread);
    try {
      const res = await api.get(`/chat/conversation/${thread.otherUser.id}`);
      setMessages(res.data);
    } catch (err) {
      console.error("Failed to fetch messages", err?.response?.data || err.message);
    }
  };

  const sendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !activeThread || isSending) return;

    setIsSending(true);
    try {
      const res = await api.post("/chat/send", {
        receiverId: activeThread.otherUser.id,
        content: newMessage
      });
      setMessages(prev => [...prev, res.data]);
      setNewMessage("");
      fetchThreads();
    } catch (err) {
      console.error("Failed to send message", err?.response?.data || err.message);
    } finally {
      setIsSending(false);
    }
  };

  const myId = typeof window !== "undefined"
    ? JSON.parse(localStorage.getItem("user") || "{}").id
    : null;

  return (
    <SellerLayout>
      <div className="h-[calc(100vh-140px)] flex bg-white rounded-3xl overflow-hidden shadow-2xl border border-[var(--border)]">
        {/* Left: Thread List */}
        <div className="w-96 border-r border-[var(--border)] flex flex-col bg-gray-50/50">
          <div className="p-8 border-b border-[var(--border)]">
            <div className="eyebrow !mb-2">Heritage Support</div>
            <h2 className="font-serif text-2xl font-bold text-[var(--charcoal)] mb-6">
              Patron <span className="text-[var(--rust)] italic lowercase">Inquiries</span>
            </h2>
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
              <div className="py-10 text-center text-[var(--muted)] animate-pulse italic text-sm">
                Synchronizing workshop threads...
              </div>
            ) : threads.length === 0 ? (
              <div className="py-10 text-center text-[var(--muted)] space-y-4">
                <MessageCircle className="w-10 h-10 mx-auto opacity-20" />
                <div className="text-sm font-bold opacity-60">NO INQUIRIES YET</div>
              </div>
            ) : threads.map((thread) => (
              <button
                key={thread.otherUser.id}
                onClick={() => fetchMessages(thread)}
                className={`w-full p-5 rounded-2xl flex items-start gap-4 transition-all duration-300 text-left ${
                  activeThread?.otherUser?.id === thread.otherUser.id
                    ? "bg-white shadow-xl ring-2 ring-[var(--rust)] scale-[1.02]"
                    : "hover:bg-white hover:shadow-lg"
                }`}
              >
                <div className="w-12 h-12 bg-[var(--bark)] rounded-xl flex items-center justify-center text-white font-serif text-lg font-bold shadow-md shrink-0">
                  {thread.otherUser.name?.[0] || "?"}
                </div>
                <div className="flex-1 min-w-0">
                  <div className="flex justify-between items-center mb-1">
                    <div className="text-sm font-bold text-[var(--charcoal)] truncate">
                      {thread.otherUser.name || "Patron"}
                    </div>
                    <div className="text-[9px] font-bold text-[var(--muted)] whitespace-nowrap ml-2">
                      {thread.timestamp
                        ? new Date(thread.timestamp).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
                        : ""}
                    </div>
                  </div>
                  <div className="text-[11px] text-[var(--muted)] line-clamp-1 opacity-70 italic">
                    {thread.lastMessage || "Start of conversation"}
                  </div>
                  {thread.unreadCount > 0 && (
                    <div className="mt-1.5 inline-flex h-4 min-w-4 px-1 items-center justify-center bg-[var(--rust)] text-white text-[8px] font-bold rounded-full">
                      {thread.unreadCount}
                    </div>
                  )}
                </div>
              </button>
            ))}
          </div>
        </div>

        {/* Right: Active Chat */}
        <div className="flex-1 flex flex-col bg-white">
          {activeThread ? (
            <>
              <div className="p-8 border-b border-[var(--border)] flex items-center justify-between bg-white z-10">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 bg-[var(--bark)] rounded-xl flex items-center justify-center text-white font-serif text-xl font-bold shadow-lg">
                    {activeThread.otherUser.name?.[0] || "?"}
                  </div>
                  <div>
                    <div className="text-lg font-bold text-[var(--charcoal)]">
                      {activeThread.otherUser.name}
                    </div>
                    <div className="text-[10px] text-green-600 font-bold uppercase tracking-widest flex items-center gap-1.5">
                      <span className="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse" />
                      Active Patron
                    </div>
                  </div>
                </div>
                <button className="p-3 hover:bg-[var(--cream)] rounded-xl transition-all text-[var(--muted)] hover:text-[var(--rust)]">
                  <MoreVertical className="w-5 h-5" />
                </button>
              </div>

              <div className="flex-1 overflow-y-auto p-10 space-y-6 bg-[var(--cream)]/20 custom-scrollbar">
                <div className="text-center">
                  <span className="bg-white px-4 py-1.5 rounded-full border border-[var(--border)] text-[8px] font-bold uppercase tracking-widest text-[var(--muted)] shadow-sm">
                    Heritage Thread Active
                  </span>
                </div>

                {messages.map((msg, i) => {
                  const isMine = String(msg.senderId) === String(myId);
                  return (
                    <motion.div
                      key={i}
                      initial={{ opacity: 0, y: 8, scale: 0.96 }}
                      animate={{ opacity: 1, y: 0, scale: 1 }}
                      className={`flex ${isMine ? "justify-end" : "justify-start"}`}
                    >
                      <div className={`max-w-md p-5 rounded-3xl shadow-lg font-medium text-sm leading-relaxed border-4 border-white ${
                        isMine
                          ? "bg-[var(--rust)] text-white rounded-tr-none"
                          : "bg-white text-[var(--charcoal)] rounded-tl-none"
                      }`}>
                        {msg.content}
                        <div className={`text-[8px] mt-2 font-bold uppercase tracking-wider opacity-60 ${isMine ? "text-white" : "text-[var(--muted)]"}`}>
                          {msg.createdAt
                            ? new Date(msg.createdAt).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" })
                            : "Just now"}
                        </div>
                      </div>
                    </motion.div>
                  );
                })}
                <div ref={scrollRef} />
              </div>

              <form onSubmit={sendMessage} className="p-6 border-t border-[var(--border)] bg-white">
                <div className="flex items-center gap-3 bg-[var(--cream)]/30 px-5 py-3 rounded-2xl border border-[var(--border)] focus-within:border-[var(--rust)] focus-within:bg-white transition-all shadow-inner">
                  <textarea
                    value={newMessage}
                    onChange={(e) => setNewMessage(e.target.value)}
                    onKeyDown={(e) => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendMessage(e); } }}
                    placeholder="Type your heritage response..."
                    rows={1}
                    className="flex-1 bg-transparent border-none outline-none text-sm font-medium italic resize-none py-1"
                  />
                  <button
                    type="submit"
                    disabled={isSending}
                    className="p-3.5 bg-[var(--rust)] text-white rounded-xl shadow-lg hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed shrink-0"
                  >
                    {isSending ? <Loader2 className="w-4 h-4 animate-spin" /> : <Send className="w-4 h-4" />}
                  </button>
                </div>
              </form>
            </>
          ) : (
            <div className="flex-1 flex flex-col items-center justify-center text-center space-y-6 p-10">
              <div className="w-24 h-24 bg-[var(--cream)] rounded-3xl flex items-center justify-center shadow-inner ring-1 ring-[var(--border)]">
                <MessageCircle className="w-10 h-10 text-[var(--muted)] opacity-30" />
              </div>
              <div>
                <h3 className="font-serif text-3xl font-bold opacity-60">Workshop Communication</h3>
                <p className="max-w-xs text-[var(--muted)] font-medium leading-relaxed italic mt-2">
                  Select a patron thread on the left to begin your mastercraft dialogue.
                </p>
              </div>
            </div>
          )}
        </div>
      </div>
    </SellerLayout>
  );
}
