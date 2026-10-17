package com.studentportal.model;

import java.sql.Timestamp;

/** Metadata of one uploaded file (a row of the student_files table). */
public class StudentFile {
    private int id;
    private int studentId;
    private String category;         // profile_image | id_proof | certificate | other
    private String originalFilename;
    private String storedFilename;
    private String filePath;         // relative to the upload folder
    private String mimeType;
    private long fileSize;
    private Timestamp uploadedAt;

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public int getStudentId() { return studentId; }
    public void setStudentId(int studentId) { this.studentId = studentId; }
    public String getCategory() { return category; }
    public void setCategory(String category) { this.category = category; }
    public String getOriginalFilename() { return originalFilename; }
    public void setOriginalFilename(String v) { this.originalFilename = v; }
    public String getStoredFilename() { return storedFilename; }
    public void setStoredFilename(String v) { this.storedFilename = v; }
    public String getFilePath() { return filePath; }
    public void setFilePath(String filePath) { this.filePath = filePath; }
    public String getMimeType() { return mimeType; }
    public void setMimeType(String mimeType) { this.mimeType = mimeType; }
    public long getFileSize() { return fileSize; }
    public void setFileSize(long fileSize) { this.fileSize = fileSize; }
    public Timestamp getUploadedAt() { return uploadedAt; }
    public void setUploadedAt(Timestamp uploadedAt) { this.uploadedAt = uploadedAt; }

    public boolean isImage() { return mimeType != null && mimeType.startsWith("image/"); }

    /** Friendly name for the category column. */
    public String getCategoryLabel() {
        switch (category == null ? "" : category) {
            case "profile_image": return "Profile image";
            case "id_proof":      return "ID proof";
            case "certificate":   return "Certificate";
            default:              return "Other";
        }
    }
}
