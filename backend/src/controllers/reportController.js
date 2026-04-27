const { Report, User } = require('../models');
const { createSafeImageFilename } = require('../utils/imageUploadSecurity');
const { ensureUploadDirs, reportsUploadDir } = require('../utils/uploadPaths');
const { notifyAdmins } = require('../utils/notificationHelper');
const { emitStatsUpdate } = require('../utils/socketUtility');
const fs = require('fs');
const path = require('path');



exports.createReport = async (req, res) => {
  try {
    const { reportedId, type, reason, description, referenceId } = req.body;
    const reporterId = req.user.id;

    if (!reportedId || !type || !reason || !description) {
      return res.status(400).json({ message: 'Missing required fields for report.' });
    }

    let evidenceUrls = [];
    
    // Handle image uploads if present
    if (req.files && req.files.length > 0) {
      ensureUploadDirs();
      for (const file of req.files) {
        const filename = createSafeImageFilename(file.originalname, file.mimetype, 'report');
        const uploadPath = path.join(reportsUploadDir, filename);
        fs.writeFileSync(uploadPath, file.buffer);
        evidenceUrls.push(`/uploads/reports/${filename}`);
      }
    }

    const report = await Report.create({
      reporterId,
      reportedId,
      type,
      reason,
      description,
      referenceId: referenceId || null,
      evidence: evidenceUrls.length > 0 ? JSON.stringify(evidenceUrls) : null,
      status: 'Pending'
    });

    // Notify all admins about the new report
    const reportedUser = await User.findByPk(reportedId, { attributes: ['name'] });
    const reporterUser = await User.findByPk(reporterId, { attributes: ['name'] });
    const typeLabel = type === 'CustomerReportingSeller' ? 'Customer reported a Seller' : 'Seller reported a Customer';
    await notifyAdmins(
      `🚨 New Report: ${typeLabel}`,
      `${reporterUser?.name || 'A user'} filed a report against ${reportedUser?.name || 'a user'}. Reason: ${reason}`,
      'system',
      '/admin/reports'
    );
    
    emitStatsUpdate({ type: 'report' });

    res.status(201).json({ message: 'Report submitted successfully.', data: report });
  } catch (error) {
    console.error('Create Report Error Stack:', error.stack);
    res.status(500).json({ 
      message: 'Internal server error while creating report.',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined 
    });
  }
};

exports.getMyReports = async (req, res) => {
  try {
    const reporterId = req.user.id;
    const reports = await Report.findAll({
      where: { reporterId },
      include: [
        { model: User, as: 'reportedUser', attributes: ['id', 'name', 'email', 'role'] }
      ],
      order: [['createdAt', 'DESC']]
    });

    res.status(200).json({ data: reports });
  } catch (error) {
    console.error('Get My Reports Error:', error);
    res.status(500).json({ message: 'Internal server error while fetching reports.' });
  }
};

exports.getAllReportsAdmin = async (req, res) => {
  try {
    const reports = await Report.findAll({
      include: [
        { model: User, as: 'reporter', attributes: ['id', 'name', 'email', 'role'] },
        { model: User, as: 'reportedUser', attributes: ['id', 'name', 'email', 'role', 'status'] }
      ],
      order: [['createdAt', 'DESC']]
    });

    res.status(200).json({ data: reports });
  } catch (error) {
    console.error('Get All Reports Error:', error);
    res.status(500).json({ message: 'Internal server error while fetching all reports.' });
  }
};

exports.resolveReport = async (req, res) => {
  try {
    const { id } = req.params;
    const { status, adminNotes, actionTaken } = req.body;

    const report = await Report.findByPk(id, {
      include: [{ model: User, as: 'reportedUser' }]
    });

    if (!report) {
      return res.status(404).json({ message: 'Report not found.' });
    }

    report.status = status || report.status;
    report.adminNotes = adminNotes || report.adminNotes;
    report.actionTaken = actionTaken || report.actionTaken;

    await report.save();

    // If the action taken involves banning or suspending
    if (actionTaken === 'Suspended' || actionTaken === 'Restricted') {
      const reportedUser = report.reportedUser;
      if (reportedUser) {
        reportedUser.status = 'blocked';
        await reportedUser.save();
      }
    }

    res.status(200).json({ message: 'Report resolved successfully.', data: report });
  } catch (error) {
    console.error('Resolve Report Error:', error);
    res.status(500).json({ message: 'Internal server error while resolving report.' });
  }
};
