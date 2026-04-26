"use client";
import React, { useState, useEffect, useRef, Suspense } from "react";
import SellerLayout from "@/components/SellerLayout";
import { MessageCircle, Search, MoreVertical, Send, Loader2, Zap, ChevronLeft, ChevronUp, ChevronDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api, getStoredUserForRole } from "@/lib/api";
import { useSocket } from "@/context/SocketContext";
import { useSearchParams } from "next/navigation";

const QUICK_REPLIES = [
  { label: "Thank You",       text: "Thank you for your interest in our handcrafted products! We appreciate your support of local Lumban artisans. 🙏" },
  { label: "Order Shipped",   text: "Great news! Your order has been packed and shipped. You should receive it within 3–5 business days." },
  { label: "Payment Confirm", text: "We have received your payment. Your order is now being prepared with the utmost care. Thank you!" },
  { label: "Still Available", text: "Yes, this item is still available! Feel free to place your order. We accept GCash and Maya." },
  { label: "Custom Order",    text: "We do accept custom orders! Please share your design preferences and quantity so we can provide a quote." },
  { label: "Processing",      text: "Your order is currently being processed. Our artisans are working on it with great care." },
  { label: "Out of Stock",    text: "We apologize, but this item is currently out of stock. Would you like to be notified when it restocks?" },
  { label: "Shipping FAQ",    text: "We ship nationwide via courier. Orders are typically dispatched within 1–2 days after payment confirmation." },
];

export default function SellerMessagesPage() {
  return (
    <Suspense fallback={<SellerLayout><div className="py-24 text-center text-[var(--muted)] animate-pulse italic text-sm">Synchronizing threads...</div></SellerLayout>}>
      <SellerMessages />
    </Suspense>
  );
}

function SellerMessages() {
  const searchParams = useSearchParams();
  const customerIdParam = searchParams.get("customerId") || searchParams.get("userId");
  const customerNameParam = searchParams.get("customerName");

  const [threads, setThreads] = useState([]);
  const [activeThread, setActiveThread] = useState(null);
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState("");
  const [loading, setLoading] = useState(true);
  const [isSending, setIsSending] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const [showQuickReplies, setShowQuickReplies] = useState(false);
  const [threadSearch, setThreadSearch] = useState("");
  const menuRef = useRef(null);
  const textareaRef = useRef(null);
  const scrollRef = useRef(null);
  const { socket } = useSocket();

  const sellerUser = typeof window !== "undefined" ? getStoredUserForRole("seller") : null;
  const myId = sellerUser?.id || null;
  const myName = sellerUser?.name || null;

  const fetchThreads = async () => {
    try { const res = await api.get("/chat/threads"); return res.data; }
    catch (err) { console.error("Failed to fetch threads"); return []; }
  };

  useEffect(() => {
    const init = async () => {
      const allThreads = await fetchThreads();
      setThreads(allThreads);
      setLoading(false);
      if (customerIdParam) {
        const existing = allThreads.find(t => String(t.otherUser?.id) === String(customerIdParam));
        if (existing) { await openThread(existing); }
        else {
          const ghost = { otherUser: { id: customerIdParam, name: decodeURIComponent(customerNameParam || "Customer"), role: "customer" }, lastMessage: null, timestamp: null, unreadCount: 0, isGhost: true };
          setActiveThread(ghost); setMessages([]); setThreads([ghost, ...allThreads]);
        }
      } else if (allThreads.length > 0) { await openThread(allThreads[0]); }
    };
    init();
  }, [customerIdParam]);

  useEffect(() => {
    if (!socket) return;
    const handleNewMessage = (msg) => {
      setActiveThread(cur => {
        if (cur && (String(msg.senderId) === String(cur.otherUser?.id) || String(msg.receiverId) === String(cur.otherUser?.id))) {
          setMessages(prev => prev.some(m => m.id === msg.id) ? prev : [...prev, msg]);
        }
        return cur;
      });
      fetchThreads().then(setThreads).catch(() => {});
    };
    socket.on("receive_message", handleNewMessage);
    return () => socket.off("receive_message", handleNewMessage);
  }, [socket]);

  useEffect(() => { scrollRef.current?.scrollIntoView({ behavior: "smooth" }); }, [messages]);

  useEffect(() => {
    const fn = (e) => { if (menuRef.current && !menuRef.current.contains(e.target)) setMenuOpen(false); };
    document.addEventListener("mousedown", fn);
    return () => document.removeEventListener("mousedown", fn);
  }, []);

  const openThread = async (thread) => {
    setActiveThread(thread);
    if (thread.isGhost) { setMessages([]); return; }
    try { const res = await api.get(`/chat/conversation/${thread.otherUser.id}`); setMessages(res.data); }
    catch (err) { console.error("Failed to fetch messages"); }
  };

  const sendMessage = async (e) => {
    e.preventDefault();
    if (!newMessage.trim() || !activeThread || isSending) return;
    setIsSending(true);
    try {
      const res = await api.post("/chat/send", { receiverId: activeThread.otherUser.id, content: newMessage });
      if (activeThread.isGhost) {
        const real = { ...activeThread, isGhost: false, lastMessage: newMessage, timestamp: new Date().toISOString() };
        setActiveThread(real);
        setThreads(prev => [real, ...prev.filter(t => t.otherUser?.id !== activeThread.otherUser?.id)]);
      }
      setMessages(prev => prev.some(m => m.id === res.data.id) ? prev : [...prev, res.data]);
      setNewMessage("");
      fetchThreads().then(setThreads);
    } catch (err) { console.error("Failed to send"); } finally { setIsSending(false); }
  };

  const handleMarkAllRead = async () => {
    if (!activeThread) return; setMenuOpen(false);
    try { await api.put(`/chat/read/${activeThread.otherUser.id}`); setThreads(prev => prev.map(t => t.otherUser?.id === activeThread.otherUser.id ? { ...t, unreadCount: 0 } : t)); } catch { }
  };
  const handleDeleteConversation = async () => {
    if (!activeThread || !confirm(`Delete all messages with ${activeThread.otherUser.name}?`)) return;
    setMenuOpen(false);
    try { await api.delete(`/chat/conversation/${activeThread.otherUser.id}`); setMessages([]); setThreads(prev => prev.filter(t => t.otherUser?.id !== activeThread.otherUser.id)); setActiveThread(null); } catch { }
  };

  const filteredThreads = threads.filter(t => {
    const q = threadSearch.trim().toLowerCase();
    if (!q) return true;
    return (t.otherUser?.name || "").toLowerCase().includes(q) || (t.lastMessage || "").toLowerCase().includes(q);
  });

  return (
    <SellerLayout>
      <div className="flex overflow-hidden bg-white lg:h-[calc(100vh-140px)] lg:rounded-2xl lg:shadow-xl lg:border lg:border-[var(--border)] max-lg:fixed max-lg:top-[72px] max-lg:left-0 max-lg:right-0 max-lg:bottom-[112px] z-10">

        {/* ── Left: Thread List ── */}
        <aside className={`${activeThread ? "hidden lg:flex" : "flex"} flex-col w-full lg:w-72 xl:w-80 border-r border-[var(--border)] bg-[#FAFAF9] shrink-0`}>
          <div className="px-4 pt-4 pb-3 border-b border-[var(--border)]">
            <div className="text-[9px] font-black uppercase tracking-[0.22em] text-[var(--muted)] mb-0.5">Heritage Support</div>
            <h2 className="font-serif text-base font-bold text-[var(--charcoal)] mb-2.5 leading-snug">Customer <span className="text-[var(--rust)] italic">inquiries</span></h2>
            <div className="relative">
              <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3 h-3 text-[var(--muted)]" />
              <input type="text" value={threadSearch} onChange={e => setThreadSearch(e.target.value)} placeholder="Filter inquiries..." className="w-full pl-7 pr-3 py-1.5 bg-white border border-[var(--border)] rounded-lg text-[10px] outline-none focus:border-[var(--rust)] transition-all" />
            </div>
          </div>
          <div className="flex-1 overflow-y-auto custom-scrollbar p-2 space-y-1">
            {loading ? <div className="py-8 text-center text-xs text-[var(--muted)] animate-pulse italic">Synchronizing...</div>
              : filteredThreads.length === 0 ? <div className="py-8 text-center"><MessageCircle className="w-8 h-8 mx-auto text-[var(--muted)] opacity-20 mb-2" /><div className="text-xs text-[var(--muted)] italic">No inquiries yet</div></div>
              : filteredThreads.map(thread => {
                const isActive = activeThread?.otherUser?.id === thread.otherUser.id;
                return (
                  <button key={thread.otherUser.id} onClick={() => openThread(thread)}
                    className={`w-full p-2.5 rounded-xl flex items-center gap-2.5 transition-all text-left ${isActive ? "bg-white shadow-md border border-[var(--rust)]/25" : "hover:bg-white hover:shadow-sm"}`}>
                    <div className="w-9 h-9 rounded-xl bg-[var(--bark)] text-white flex items-center justify-center text-sm font-bold shrink-0">{thread.otherUser.name?.[0] || "?"}</div>
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between gap-1 mb-0.5">
                        <div className="flex items-center gap-1 min-w-0">
                          <span className="text-[11px] font-bold text-[var(--charcoal)] truncate">{thread.otherUser.name || "Customer"}</span>
                          <span className={`text-[7px] px-1 py-px rounded font-bold uppercase shrink-0 ${thread.otherUser.role === "seller" ? "bg-[var(--rust)] text-white" : "bg-[var(--charcoal)] text-white"}`}>{thread.otherUser.role}</span>
                        </div>
                        <span className="text-[9px] text-[var(--muted)] whitespace-nowrap shrink-0">{thread.isGhost ? "New" : thread.timestamp ? new Date(thread.timestamp).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : ""}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <p className="text-[10px] text-[var(--muted)] truncate flex-1">{thread.isGhost ? "Start conversation" : (thread.lastMessage || "Click to view")}</p>
                        {thread.unreadCount > 0 && <span className="bg-[var(--rust)] text-white text-[7px] font-bold min-w-[14px] h-3.5 px-1 rounded-full flex items-center justify-center shrink-0">{thread.unreadCount}</span>}
                      </div>
                    </div>
                  </button>
                );
              })}
          </div>
        </aside>

        {/* ── Right: Chat Area ── */}
        <div className={`${activeThread ? "flex" : "hidden lg:flex"} flex-1 flex-col min-h-0 bg-white`}>
          {activeThread ? (
            <>
              {/* Chat Header */}
              <div className="px-4 py-3 border-b border-[var(--border)] flex items-center justify-between shrink-0">
                <div className="flex items-center gap-2.5 min-w-0 flex-1">
                  <button onClick={() => setActiveThread(null)} className="md:hidden p-1 -ml-1 text-[var(--muted)] hover:text-[var(--rust)] transition-colors shrink-0"><ChevronLeft className="w-5 h-5" /></button>
                  <div className="w-9 h-9 rounded-xl bg-[var(--bark)] text-white flex items-center justify-center text-sm font-bold shrink-0">{activeThread.otherUser.name?.[0] || "?"}</div>
                  <div className="min-w-0">
                    <div className="flex items-center gap-1.5 flex-wrap">
                      <h2 className="text-sm font-bold text-[var(--charcoal)] leading-tight truncate">{activeThread.otherUser.name}</h2>
                      <span className={`text-[7px] px-1.5 py-px rounded font-bold uppercase tracking-wide shrink-0 ${activeThread.otherUser.role === "seller" ? "bg-[var(--rust)] text-white" : "bg-[var(--charcoal)] text-white"}`}>{activeThread.otherUser.role}</span>
                    </div>
                    <div className="text-[9px] font-bold uppercase tracking-widest mt-px">
                      {activeThread.isGhost ? <span className="text-amber-500">● New Inquiry</span> : <span className="text-green-600">● Active Session</span>}
                    </div>
                  </div>
                </div>
                <div className="relative shrink-0" ref={menuRef}>
                  <button onClick={() => setMenuOpen(p => !p)} className={`p-2 rounded-xl transition-all ${menuOpen ? "bg-[var(--rust)] text-white" : "hover:bg-[var(--cream)] text-[var(--muted)]"}`}><MoreVertical className="w-4 h-4" /></button>
                  {menuOpen && (
                    <div className="absolute right-0 top-[calc(100%+6px)] z-50 w-52 bg-white rounded-xl shadow-2xl border border-[var(--border)] overflow-hidden">
                      <a href={`/seller/customer?id=${activeThread.otherUser.id}`} onClick={() => setMenuOpen(false)} className="flex items-center gap-2 px-4 py-3 text-xs font-semibold text-[var(--charcoal)] hover:bg-[var(--cream)] transition-colors">👤 View Customer Profile</a>
                      <button onClick={handleMarkAllRead} className="w-full flex items-center gap-2 px-4 py-3 text-xs font-semibold text-[var(--charcoal)] hover:bg-[var(--cream)] transition-colors text-left">✓ Mark All Read</button>
                      <div className="h-px bg-[var(--border)] mx-3" />
                      <button onClick={handleDeleteConversation} className="w-full flex items-center gap-2 px-4 py-3 text-xs font-semibold text-red-500 hover:bg-red-50 transition-colors text-left">🗑 Delete Conversation</button>
                    </div>
                  )}
                </div>
              </div>

              {/* Messages */}
              <div className="flex-1 overflow-y-auto p-4 space-y-2 bg-[var(--cream)]/20 custom-scrollbar min-h-0">
                {messages.length === 0 && (
                  <div className="text-center py-6"><span className="bg-white px-4 py-1.5 rounded-full border border-[var(--border)] text-[9px] font-bold uppercase tracking-widest text-[var(--muted)] shadow-sm">{activeThread.isGhost ? "Send your first message" : "Heritage Thread Active"}</span></div>
                )}
                {messages.map((msg, i) => {
                  const isMine = String(msg.senderId || msg.sender?.id) === String(myId);
                  const senderName = isMine ? (myName || "You") : (msg.sender?.name || activeThread.otherUser.name || "Customer");
                  return (
                    <motion.div key={i} initial={{ opacity: 0, scale: 0.98 }} animate={{ opacity: 1, scale: 1 }} className={`flex items-end gap-2 ${isMine ? "justify-end" : "justify-start"}`}>
                      {!isMine && <div className="w-6 h-6 rounded-lg bg-[var(--bark)] text-white text-[9px] font-bold flex items-center justify-center shrink-0 mb-0.5">{senderName[0]}</div>}
                      <div className={`max-w-[70%] px-3 py-2 rounded-xl text-[11px] leading-relaxed ${isMine ? "bg-[var(--rust)] text-white rounded-br-sm" : "bg-white text-[var(--charcoal)] border border-[var(--border)] rounded-bl-sm shadow-sm"}`}>
                        {msg.content}
                        <div className={`text-[8px] mt-1 font-bold uppercase tracking-widest opacity-70 ${isMine ? "text-right" : "text-left"}`}>{msg.createdAt ? new Date(msg.createdAt).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }) : "Just now"}</div>
                      </div>
                      {isMine && <div className="w-6 h-6 rounded-lg bg-[var(--rust)] text-white text-[9px] font-bold flex items-center justify-center shrink-0 mb-0.5">{myName?.[0] || "S"}</div>}
                    </motion.div>
                  );
                })}
                <div ref={scrollRef} />
              </div>

              {/* Quick Replies + Input */}
              <div className="border-t border-[var(--border)] bg-white shrink-0">
                <AnimatePresence>
                  {showQuickReplies && (
                    <motion.div initial={{ height: 0, opacity: 0 }} animate={{ height: "auto", opacity: 1 }} exit={{ height: 0, opacity: 0 }} transition={{ duration: 0.18 }} className="overflow-hidden border-b border-[var(--border)]">
                      <div className="px-4 py-3">
                        <div className="text-[9px] font-bold uppercase tracking-[0.2em] text-[var(--muted)] mb-2">Quick Replies</div>
                        <div className="flex flex-wrap gap-1.5">
                          {QUICK_REPLIES.map(qr => (
                            <button key={qr.label} type="button" onClick={() => { setNewMessage(qr.text); setShowQuickReplies(false); setTimeout(() => textareaRef.current?.focus(), 50); }}
                              className="px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider border border-[var(--border)] rounded-full bg-[var(--cream)] hover:bg-[var(--rust)] hover:text-white hover:border-[var(--rust)] transition-all active:scale-95">{qr.label}</button>
                          ))}
                        </div>
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>
                <form onSubmit={sendMessage} className="px-4 py-3">
                  <div className="flex items-center gap-2 bg-[var(--cream)]/40 border border-[var(--border)] rounded-xl px-3 py-1.5 focus-within:border-[var(--rust)] focus-within:bg-white transition-all">
                    <button type="button" onClick={() => setShowQuickReplies(p => !p)} title="Quick Replies" className={`p-1.5 rounded-lg transition-all shrink-0 ${showQuickReplies ? "bg-[var(--rust)] text-white" : "text-[var(--muted)] hover:text-[var(--rust)]"}`}>
                      <Zap className="w-3.5 h-3.5" />
                    </button>
                    <textarea ref={textareaRef} value={newMessage} onChange={e => setNewMessage(e.target.value)} onKeyDown={e => { if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); sendMessage(e); } }}
                      placeholder={activeThread.isGhost ? "Start a conversation..." : "Type response..."}
                      rows={1} className="flex-1 bg-transparent border-none outline-none text-xs resize-none py-1 min-h-[20px] max-h-[80px] text-[var(--charcoal)] placeholder:text-[var(--muted)]" />
                    <button type="submit" disabled={isSending} className="w-7 h-7 bg-[var(--rust)] text-white rounded-lg flex items-center justify-center hover:bg-[var(--bark)] transition-all disabled:opacity-50 shrink-0">
                      {isSending ? <Loader2 className="w-3 h-3 animate-spin" /> : <Send className="w-3 h-3" />}
                    </button>
                  </div>
                </form>
              </div>
            </>
          ) : (
            <div className="flex-1 flex flex-col items-center justify-center text-center p-8 space-y-4">
              <div className="w-16 h-16 bg-[var(--cream)] rounded-2xl flex items-center justify-center"><MessageCircle className="w-7 h-7 text-[var(--muted)] opacity-40" /></div>
              <div><h3 className="font-serif text-base font-bold opacity-60">Workshop Communication</h3><p className="text-[11px] text-[var(--muted)] italic mt-1">Select a customer thread to begin.</p></div>
            </div>
          )}
        </div>
      </div>
    </SellerLayout>
  );
}
