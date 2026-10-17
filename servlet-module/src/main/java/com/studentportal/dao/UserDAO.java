package com.studentportal.dao;

import com.studentportal.config.DatabaseUtil;
import com.studentportal.model.User;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Optional;
import org.mindrot.jbcrypt.BCrypt;

/** Database access for the users table (login). */
public class UserDAO {

    // A valid bcrypt hash of a random string: checked for unknown usernames so that
    // the response time does not reveal whether the account exists.
    private static final String DUMMY_HASH = "$2a$10$lI9AWmmdaO8BS7tisb2lEuz8/0pKwaFZGfL8CHGR4OE5lVwMjYpUy";

    /**
     * Verifies username + password. Returns the user, or empty when the login is wrong.
     * @throws SQLException when the database is not reachable (caller shows a generic error)
     */
    public Optional<User> authenticate(String username, String password) throws SQLException {
        String sql = "SELECT id, username, password_hash, role, student_id FROM users WHERE username = ?";
        try (Connection con = DatabaseUtil.getConnection();
             PreparedStatement ps = con.prepareStatement(sql)) {
            ps.setString(1, username);
            try (ResultSet rs = ps.executeQuery()) {
                if (!rs.next()) {
                    safeCheck(password, DUMMY_HASH);          // equalise timing, then fail
                    return Optional.empty();
                }
                if (!safeCheck(password, rs.getString("password_hash"))) {
                    return Optional.empty();
                }
                int studentIdRaw = rs.getInt("student_id");
                Integer studentId = rs.wasNull() ? null : studentIdRaw;
                User user = new User(rs.getInt("id"), rs.getString("username"), rs.getString("role"), studentId);
                touchLastLogin(con, user.getId());
                return Optional.of(user);
            }
        }
    }

    private void touchLastLogin(Connection con, int userId) throws SQLException {
        try (PreparedStatement ps = con.prepareStatement("UPDATE users SET last_login_at = NOW() WHERE id = ?")) {
            ps.setInt(1, userId);
            ps.executeUpdate();
        }
    }

    /**
     * PHP's password_hash() writes bcrypt hashes with the prefix "$2y$"; jBCrypt only knows
     * "$2a$". Both prefixes describe the SAME algorithm, so we simply rename the prefix.
     */
    private boolean safeCheck(String plain, String hash) {
        if (hash == null) {
            return false;
        }
        try {
            String normalised = hash.startsWith("$2y$") ? "$2a$" + hash.substring(4) : hash;
            return BCrypt.checkpw(plain, normalised);
        } catch (IllegalArgumentException e) {   // malformed hash in the database
            return false;
        }
    }
}
