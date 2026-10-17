package com.studentportal.config;

import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.Properties;

/**
 * Central place for database and upload-folder settings.
 * Values come from environment variables first, then from application.properties
 * on the classpath. Nothing secret is written in source code.
 * Connections are created per request and closed by the DAO's try-with-resources
 * (simple and fine for a college project; production code would use a connection pool).
 */
public final class DatabaseUtil {

    private static final Properties PROPS = new Properties();

    static {
        // Load application.properties if it exists (it is optional when env vars are used)
        try (InputStream in = DatabaseUtil.class.getClassLoader().getResourceAsStream("application.properties")) {
            if (in != null) {
                PROPS.load(in);
            }
        } catch (IOException e) {
            throw new ExceptionInInitializerError("Cannot read application.properties: " + e.getMessage());
        }
        // Registering the driver by name works reliably inside Tomcat's class loaders
        String driver = setting("DB_DRIVER", "db.driver", "com.mysql.cj.jdbc.Driver");
        try {
            Class.forName(driver);
        } catch (ClassNotFoundException e) {
            throw new ExceptionInInitializerError("JDBC driver not found: " + driver);
        }
    }

    private DatabaseUtil() { }

    /** env var > properties file > default */
    private static String setting(String envName, String propName, String defaultValue) {
        String env = System.getenv(envName);
        if (env != null && !env.isEmpty()) {
            return env;
        }
        return PROPS.getProperty(propName, defaultValue);
    }

    /** Opens a new connection. The caller MUST close it (use try-with-resources). */
    public static Connection getConnection() throws SQLException {
        return DriverManager.getConnection(
            setting("DB_URL", "db.url", "jdbc:mysql://localhost:3306/student_portal?useSSL=false&allowPublicKeyRetrieval=true&serverTimezone=UTC"),
            setting("DB_USER", "db.user", "root"),
            setting("DB_PASSWORD", "db.password", ""));
    }

    /** Folder that holds uploaded files (shared with the PHP module). */
    public static Path getUploadDir() {
        return Paths.get(setting("UPLOAD_DIR", "upload.dir", "../php-module/uploads"));
    }
}
