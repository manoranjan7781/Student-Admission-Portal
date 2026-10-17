package com.studentportal.dao;

import com.studentportal.config.DatabaseUtil;
import com.studentportal.model.Student;
import com.studentportal.model.StudentFile;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

/** Read-only JDBC access to students and student_files (the PHP module does the writing). */
public class StudentDAO {

    private static final String FILE_COLUMNS =
        "id, student_id, category, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at";

    /** All students with their files: 2 queries total (no "one query per student"). */
    public List<Student> findAllWithFiles() throws SQLException {
        Map<Integer, Student> byId = new LinkedHashMap<>();
        try (Connection con = DatabaseUtil.getConnection()) {
            try (PreparedStatement ps = con.prepareStatement(
                     "SELECT id, name, roll_number, email, course, created_at FROM students ORDER BY name");
                 ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Student s = mapStudent(rs);
                    byId.put(s.getId(), s);
                }
            }
            try (PreparedStatement ps = con.prepareStatement(
                     "SELECT " + FILE_COLUMNS + " FROM student_files ORDER BY id");
                 ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    StudentFile f = mapFile(rs);
                    Student owner = byId.get(f.getStudentId());
                    if (owner != null) {
                        owner.getFiles().add(f);
                    }
                }
            }
        }
        return new ArrayList<>(byId.values());
    }

    /** One student with files, or empty if the id does not exist. */
    public Optional<Student> findByIdWithFiles(int id) throws SQLException {
        try (Connection con = DatabaseUtil.getConnection()) {
            Student s;
            try (PreparedStatement ps = con.prepareStatement(
                    "SELECT id, name, roll_number, email, course, created_at FROM students WHERE id = ?")) {
                ps.setInt(1, id);
                try (ResultSet rs = ps.executeQuery()) {
                    if (!rs.next()) {
                        return Optional.empty();
                    }
                    s = mapStudent(rs);
                }
            }
            try (PreparedStatement ps = con.prepareStatement(
                    "SELECT " + FILE_COLUMNS + " FROM student_files WHERE student_id = ? ORDER BY category, id")) {
                ps.setInt(1, id);
                try (ResultSet rs = ps.executeQuery()) {
                    while (rs.next()) {
                        s.getFiles().add(mapFile(rs));
                    }
                }
            }
            return Optional.of(s);
        }
    }

    /** Looks a file up by its database id (the browser never sends a path). */
    public Optional<StudentFile> findFileById(int fileId) throws SQLException {
        try (Connection con = DatabaseUtil.getConnection();
             PreparedStatement ps = con.prepareStatement("SELECT " + FILE_COLUMNS + " FROM student_files WHERE id = ?")) {
            ps.setInt(1, fileId);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? Optional.of(mapFile(rs)) : Optional.empty();
            }
        }
    }

    private Student mapStudent(ResultSet rs) throws SQLException {
        Student s = new Student();
        s.setId(rs.getInt("id"));
        s.setName(rs.getString("name"));
        s.setRollNumber(rs.getString("roll_number"));
        s.setEmail(rs.getString("email"));
        s.setCourse(rs.getString("course"));
        s.setCreatedAt(rs.getTimestamp("created_at"));
        return s;
    }

    private StudentFile mapFile(ResultSet rs) throws SQLException {
        StudentFile f = new StudentFile();
        f.setId(rs.getInt("id"));
        f.setStudentId(rs.getInt("student_id"));
        f.setCategory(rs.getString("category"));
        f.setOriginalFilename(rs.getString("original_filename"));
        f.setStoredFilename(rs.getString("stored_filename"));
        f.setFilePath(rs.getString("file_path"));
        f.setMimeType(rs.getString("mime_type"));
        f.setFileSize(rs.getLong("file_size"));
        f.setUploadedAt(rs.getTimestamp("uploaded_at"));
        return f;
    }
}
