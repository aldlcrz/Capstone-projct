"use client";
import React, { useState, useEffect, useCallback } from "react";
import AdminLayout from "@/components/AdminLayout";
import { 
  ShieldAlert, 
  Search, 
  Filter, 
  CheckCircle2, 
  XCircle, 
  Eye, 
  MessageSquare, 
  User, 
  Clock,
  AlertTriangle,
  ChevronRight,
  ExternalLink,
  Ban,
  ShieldCheck
} from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { api } from "@/lib/api";
import { formatNotificationTime } from "@/lib/notifications";

export default function AdminReports() {
  const [reports, setReports] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterStatus, setFilterStatus] = useState("All");
  const [selectedReport, setSelectedReport] = useState(null);
  const [isResolving, setIsResolving] = useState(false);
  const [resolutionData, setResolutionData] = useState({
    status: "Resolved",
    adminNotes: "",
    actionTaken: "None"
  });

  const fetchReports = useCallback(async () => {
    try {
      setLoading(true);
      const res = await api.get("/reports/admin/all");
      setReports(res.data.data);
    } catch (err) {
      console.error("Failed to fetch reports:", err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchReports();
  }, [fetchReports]);

  const handleResolve = async (e) => {
    e.preventDefault();
    if (!resolutionData.adminNotes.trim()) {
      alert("Please provide internal notes for the resolution.");
      return;
    }

    try {
      setIsResolving(true);
      await api.put(`/reports/admin/${selectedReport.id}/resolve`, resolutionData);
      setSelectedReport(null);
      fetchReports();
    } catch (err) {
      console.error("Failed to resolve report:", err);
      alert(err.response?.data?.message || "Failed to resolve report.");
    } finally {
      setIsResolving(false);
    }
  };

  const filteredReports = reports.filter(r => {
    const matchesSearch = 
      r.reporter?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      r.reportedUser?.name?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      r.reason.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = filterStatus === "All" || r.status === filterStatus;
    
    return matchesSearch && matchesStatus;
  });

  const getStatusColor = (status) => {
    switch (status) {
      case 'Pending': return 'bg-orange-50 text-orange-700 border-orange-100';
      case 'In Review': return 'bg-blue-50 text-blue-700 border-blue-100';
      case 'Resolved': return 'bg-green-50 text-green-700 border-green-100';
      case 'Dismissed': return 'bg-gray-50 text-gray-600 border-gray-100';
      default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
  };

  return (
    <AdminLayout>
      <div className="space-y-6 mb-20">
        {/* Header */}
        <div className="flex flex-col md:flex-row md:items-end justify-between gap-6">
          <div>
            <div className="eyebrow">Trust & Safety</div>
            <h1 className="font-serif text-xl font-bold tracking-tight text-[var(--charcoal)] uppercase">
              Incident <span className="text-[var(--rust)] italic lowercase">Registry</span>
            </h1>
            <p className="text-[9px] font-black text-[var(--muted)] opacity-50 uppercase tracking-widest mt-1">
              Investigating misconduct and platform violations.
            </p>
          </div>
          
          <div className="flex flex-wrap items-center gap-3">
             {/* Filter Tabs */}
             <div className="flex items-center p-1 bg-white border border-[var(--border)] rounded-2xl shadow-sm">
                {["All", "Pending", "Resolved"].map((status) => (
                  <button
                    key={status}
                    onClick={() => setFilterStatus(status)}
                    className={`px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all ${filterStatus === status ? 'bg-red-50 text-[var(--rust)]' : 'text-[var(--muted)] hover:bg-[var(--cream)]'}`}
                  >
                    {status}
                  </button>
                ))}
             </div>

             <div className="relative group">
                <Search className="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-[var(--muted)]" />
                <input
                  type="text"
                  placeholder="Search by names or reason..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-12 pr-4 py-3 bg-white border border-[var(--border)] rounded-2xl outline-none focus:border-[var(--rust)] transition-all font-bold text-[10px] uppercase tracking-wider w-64 shadow-sm"
                />
             </div>
          </div>
        </div>

        {/* Reports Table */}
        <div className="artisan-card p-0 overflow-hidden shadow-xl border-none bg-white/80 backdrop-blur-xl">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-[var(--cream)]/30 border-b border-[var(--border)]">
                  <th className="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Report Details</th>
                  <th className="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Reporter</th>
                  <th className="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Accused</th>
                  <th className="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic whitespace-nowrap">Status</th>
                  <th className="px-6 py-4 text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-70 italic text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-[var(--border)]">
                {loading ? (
                  <tr><td colSpan="5" className="px-6 py-12 text-center text-[var(--muted)] italic animate-pulse">Retrieving incident logs...</td></tr>
                ) : filteredReports.length === 0 ? (
                  <tr><td colSpan="5" className="px-6 py-12 text-center text-[var(--muted)] italic">No incident reports found.</td></tr>
                ) : (
                  filteredReports.map((report) => (
                    <tr key={report.id} className="group hover:bg-gray-50/50 transition-colors">
                      <td className="px-6 py-5">
                        <div className="space-y-1">
                          <div className="text-[10px] font-black text-red-600 uppercase tracking-widest">{report.reason}</div>
                          <div className="text-[11px] text-[var(--charcoal)] font-bold line-clamp-1">{report.description}</div>
                          <div className="text-[8px] text-[var(--muted)] font-black uppercase tracking-widest flex items-center gap-1 opacity-60">
                            <Clock className="w-2.5 h-2.5" /> {formatNotificationTime(report.createdAt)}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-5">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-lg bg-[var(--cream)] border border-[var(--border)] flex items-center justify-center text-[10px] font-black text-[var(--charcoal)]">
                            {report.reporter?.name?.[0].toUpperCase()}
                          </div>
                          <div>
                            <div className="text-[11px] font-black text-[var(--charcoal)] uppercase tracking-tight">{report.reporter?.name}</div>
                            <div className="text-[8px] text-[var(--muted)] font-bold">{report.reporter?.role}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-5">
                        <div className="flex items-center gap-3">
                          <div className="w-8 h-8 rounded-lg bg-red-50 border border-red-100 flex items-center justify-center text-[10px] font-black text-red-700">
                            {report.reportedUser?.name?.[0].toUpperCase()}
                          </div>
                          <div>
                            <div className="text-[11px] font-black text-[var(--charcoal)] uppercase tracking-tight">{report.reportedUser?.name}</div>
                            <div className="text-[8px] text-[var(--muted)] font-bold">{report.reportedUser?.role}</div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-5">
                        <span className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black border uppercase tracking-widest italic shadow-sm ${getStatusColor(report.status)}`}>
                          {report.status}
                        </span>
                      </td>
                      <td className="px-6 py-5 text-right">
                        <button 
                          onClick={() => {
                            setSelectedReport(report);
                            setResolutionData({
                              status: report.status === "Pending" ? "In Review" : report.status,
                              adminNotes: report.adminNotes || "",
                              actionTaken: report.actionTaken || "None"
                            });
                          }}
                          className="p-2.5 bg-white text-[var(--muted)] hover:text-[var(--rust)] rounded-xl border border-[var(--border)] shadow-sm hover:border-[var(--rust)] transition-all"
                        >
                          <Eye className="w-4 h-4" />
                        </button>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>

        {/* Resolution Modal */}
        <AnimatePresence>
          {selectedReport && (
            <div className="fixed inset-0 z-[100] flex items-center justify-center p-4">
              <motion.div
                initial={{ opacity: 0 }}
                animate={{ opacity: 1 }}
                exit={{ opacity: 0 }}
                onClick={() => setSelectedReport(null)}
                className="absolute inset-0 bg-[var(--charcoal)]/40 backdrop-blur-sm"
              />
              <motion.div
                initial={{ opacity: 0, scale: 0.95, y: 20 }}
                animate={{ opacity: 1, scale: 1, y: 0 }}
                exit={{ opacity: 0, scale: 0.95, y: 20 }}
                className="relative w-full max-w-2xl bg-white rounded-[2rem] shadow-2xl overflow-hidden"
              >
                <div className="h-full flex flex-col">
                  {/* Modal Header */}
                  <div className="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                    <div>
                      <div className="text-[9px] font-black text-red-600 uppercase tracking-[0.2em] mb-1">Violation Investigation</div>
                      <h3 className="font-serif text-xl font-bold text-[var(--charcoal)] tracking-tighter uppercase">
                        Case #<span className="text-[var(--rust)]">{selectedReport.id.slice(0, 8)}</span>
                      </h3>
                    </div>
                    <button onClick={() => setSelectedReport(null)} className="p-3 hover:bg-white rounded-2xl transition-all shadow-sm">
                      <XCircle className="w-6 h-6 text-[var(--muted)]" />
                    </button>
                  </div>

                  <div className="flex-1 overflow-y-auto p-8 space-y-8 max-h-[70vh]">
                    {/* Evidence & Details */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                      <div className="space-y-4">
                        <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-60">Incident Description</label>
                        <div className="p-5 bg-[var(--cream)] rounded-2xl border border-[var(--border)] text-xs font-medium leading-relaxed italic text-[var(--charcoal)]">
                          "{selectedReport.description}"
                        </div>
                        
                        <div className="grid grid-cols-2 gap-4">
                           <div className="space-y-2">
                             <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-60">Reporter</label>
                             <div className="flex items-center gap-2 p-3 bg-white border border-[var(--border)] rounded-xl">
                               <div className="w-6 h-6 rounded bg-[var(--bark)] text-white text-[8px] flex items-center justify-center font-bold">{selectedReport.reporter?.name?.[0]}</div>
                               <span className="text-[10px] font-bold truncate">{selectedReport.reporter?.name}</span>
                             </div>
                           </div>
                           <div className="space-y-2">
                             <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-60">Accused</label>
                             <div className="flex items-center gap-2 p-3 bg-red-50 border border-red-100 rounded-xl">
                               <div className="w-6 h-6 rounded bg-red-600 text-white text-[8px] flex items-center justify-center font-bold">{selectedReport.reportedUser?.name?.[0]}</div>
                               <span className="text-[10px] font-bold truncate">{selectedReport.reportedUser?.name}</span>
                             </div>
                           </div>
                        </div>
                      </div>

                      <div className="space-y-4">
                        <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)] opacity-60">Evidence Provided</label>
                        {selectedReport.evidence ? (
                          <div className="grid grid-cols-2 gap-3">
                            {JSON.parse(selectedReport.evidence).map((url, i) => (
                              <a key={i} href={url} target="_blank" rel="noopener noreferrer" className="aspect-square rounded-xl border border-[var(--border)] overflow-hidden group relative">
                                <img src={url} className="w-full h-full object-cover transition-transform group-hover:scale-110" alt="Evidence" />
                                <div className="absolute inset-0 bg-[var(--charcoal)]/40 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                                  <ExternalLink className="w-5 h-5 text-white" />
                                </div>
                              </a>
                            ))}
                          </div>
                        ) : (
                          <div className="aspect-video rounded-2xl bg-gray-50 border border-dashed border-gray-200 flex flex-col items-center justify-center text-[var(--muted)]">
                            <AlertTriangle className="w-8 h-8 opacity-20 mb-2" />
                            <span className="text-[10px] font-black uppercase tracking-widest opacity-40">No media evidence</span>
                          </div>
                        )}
                      </div>
                    </div>

                    {/* Resolution Form */}
                    <form onSubmit={handleResolve} className="space-y-6 pt-6 border-t border-gray-100">
                       <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                          <div className="space-y-2">
                            <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Status Update</label>
                            <select 
                              value={resolutionData.status}
                              onChange={(e) => setResolutionData({...resolutionData, status: e.target.value})}
                              className="w-full p-4 bg-[var(--cream)]/30 border border-[var(--border)] rounded-2xl text-[11px] font-bold outline-none focus:border-[var(--rust)] transition-all"
                            >
                              <option value="Pending">Pending</option>
                              <option value="In Review">In Review</option>
                              <option value="Resolved">Resolved</option>
                              <option value="Dismissed">Dismissed</option>
                            </select>
                          </div>
                          <div className="space-y-2">
                            <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Enforcement Action</label>
                            <select 
                              value={resolutionData.actionTaken}
                              onChange={(e) => setResolutionData({...resolutionData, actionTaken: e.target.value})}
                              className="w-full p-4 bg-red-50/30 border border-red-100 rounded-2xl text-[11px] font-bold outline-none focus:border-red-600 transition-all text-red-700"
                            >
                              <option value="None">No Enforcement</option>
                              <option value="Warning">Issue Formal Warning</option>
                              <option value="Restricted">Restrict Account</option>
                              <option value="Suspended">Suspend/Ban Account</option>
                            </select>
                          </div>
                       </div>

                       <div className="space-y-2">
                          <label className="text-[9px] font-black uppercase tracking-widest text-[var(--muted)]">Internal Investigation Notes</label>
                          <textarea 
                            value={resolutionData.adminNotes}
                            onChange={(e) => setResolutionData({...resolutionData, adminNotes: e.target.value})}
                            placeholder="Detailed findings of the investigation..."
                            rows="4"
                            className="w-full p-5 bg-[var(--cream)]/30 border border-[var(--border)] rounded-2xl text-xs font-medium outline-none focus:border-[var(--rust)] transition-all min-h-[120px]"
                          />
                       </div>

                       <div className="flex gap-4 pt-4">
                          <button 
                            type="button"
                            onClick={() => setSelectedReport(null)}
                            className="flex-1 py-4 bg-[var(--cream)] text-[var(--charcoal)] text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[var(--border)] transition-all"
                          >
                            Close
                          </button>
                          <button 
                            type="submit"
                            disabled={isResolving}
                            className="flex-[2] py-4 bg-[var(--rust)] text-white text-[11px] font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[var(--charcoal)] transition-all shadow-xl active:scale-[0.98] disabled:opacity-50"
                          >
                            {isResolving ? "Synchronizing..." : "Resolve Incident"}
                          </button>
                       </div>
                    </form>
                  </div>
                </div>
              </motion.div>
            </div>
          )}
        </AnimatePresence>
      </div>
    </AdminLayout>
  );
}
