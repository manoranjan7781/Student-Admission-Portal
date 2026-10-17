package com.studentportal.model;

import java.sql.Timestamp;
import java.util.ArrayList;
import java.util.List;

/** A student plus the list of his/her uploaded files. */
public class Student {
    private int id;
    private String name;
    private String rollNumber;
    private String email;
    private String course;
    private Timestamp createdAt;
    private final List<StudentFile> files = new ArrayList<>();

    public int getId() { return id; }
    public void setId(int id) { this.id = id; }
    public String getName() { return name; }
    public void setName(String name) { this.name = name; }
    public String getRollNumber() { return rollNumber; }
    public void setRollNumber(String rollNumber) { this.rollNumber = rollNumber; }
    public String getEmail() { return email; }
    public void setEmail(String email) { this.email = email; }
    public String getCourse() { return course; }
    public void setCourse(String course) { this.course = course; }
    public Timestamp getCreatedAt() { return createdAt; }
    public void setCreatedAt(Timestamp createdAt) { this.createdAt = createdAt; }
    public List<StudentFile> getFiles() { return files; }

    /** The newest profile image, or null when the student has none (JSP handles the null case). */
    public StudentFile getProfileImage() {
        StudentFile latest = null;
        for (StudentFile f : files) {
            if ("profile_image".equals(f.getCategory()) && (latest == null || f.getId() > latest.getId())) {
                latest = f;
            }
        }
        return latest;
    }

    /** All files except the profile image (ID proof, certificates, ...). */
    public List<StudentFile> getDocuments() {
        List<StudentFile> docs = new ArrayList<>();
        for (StudentFile f : files) {
            if (!"profile_image".equals(f.getCategory())) {
                docs.add(f);
            }
        }
        return docs;
    }
}
