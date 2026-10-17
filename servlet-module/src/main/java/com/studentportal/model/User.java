package com.studentportal.model;

/** A logged-in account. Stored in the HTTP session (never contains the password hash). */
public class User {
    private final int id;
    private final String username;
    private final String role;        // "admin" or "student"
    private final Integer studentId;  // null for admins

    public User(int id, String username, String role, Integer studentId) {
        this.id = id;
        this.username = username;
        this.role = role;
        this.studentId = studentId;
    }

    public int getId() { return id; }
    public String getUsername() { return username; }
    public String getRole() { return role; }
    public Integer getStudentId() { return studentId; }
    public boolean isAdmin() { return "admin".equals(role); }

    /** Authorisation rule shared by the profile page and the file endpoint. */
    public boolean canAccessStudent(int targetStudentId) {
        return isAdmin() || (studentId != null && studentId == targetStudentId);
    }
}
