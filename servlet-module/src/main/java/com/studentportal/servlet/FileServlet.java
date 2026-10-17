package com.studentportal.servlet;

import com.studentportal.config.DatabaseUtil;
import com.studentportal.dao.StudentDAO;
import com.studentportal.filter.AuthFilter;
import com.studentportal.model.StudentFile;
import com.studentportal.model.User;
import com.studentportal.util.Errors;
import jakarta.servlet.ServletException;
import jakarta.servlet.ServletOutputStream;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.io.InputStream;
import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.NoSuchFileException;
import java.nio.file.Path;
import java.sql.SQLException;
import java.util.Optional;
import java.util.Set;
import java.util.logging.Level;
import java.util.logging.Logger;

/**
 * URL: /file?id=N  (use as  &lt;img src="file?id=N"&gt;  or as a download link)
 * Streams a stored image/document to the browser after checking:
 *   1. the user is logged in (AuthFilter), 2. the user may access that student's files,
 *   3. the resolved path is really inside the upload folder.
 * The request carries only a database id - never a file name or path.
 */
public class FileServlet extends HttpServlet {
    private static final long serialVersionUID = 1L;
    private static final Logger LOG = Logger.getLogger(FileServlet.class.getName());
    private static final Set<String> INLINE_TYPES =
        Set.of("image/jpeg", "image/png", "image/webp", "application/pdf");
    private final StudentDAO studentDAO = new StudentDAO();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        User user = (User) req.getSession().getAttribute(AuthFilter.SESSION_USER);

        int fileId;
        try {
            fileId = Integer.parseInt(req.getParameter("id"));
            if (fileId < 1) throw new NumberFormatException();
        } catch (NumberFormatException e) {
            Errors.send(req, resp, 400, "Invalid file id.");
            return;
        }

        StudentFile file;
        try {
            Optional<StudentFile> found = studentDAO.findFileById(fileId);
            if (!found.isPresent()) {
                Errors.send(req, resp, 404, "File not found.");
                return;
            }
            file = found.get();
        } catch (SQLException e) {
            LOG.log(Level.SEVERE, "Database error while loading file " + fileId, e);
            Errors.send(req, resp, 500, "The file could not be loaded right now.");
            return;
        }

        // Authorisation BEFORE any byte is sent
        if (!user.canAccessStudent(file.getStudentId())) {
            Errors.send(req, resp, 403, "You do not have permission to open this file.");
            return;
        }

        // Resolve the stored relative path and make sure it cannot escape the upload folder
        Path root;
        Path target;
        try {
            root = DatabaseUtil.getUploadDir().toRealPath();
            target = root.resolve(file.getFilePath()).normalize();
            if (!target.startsWith(root) || !Files.isRegularFile(target)) {
                throw new NoSuchFileException(file.getFilePath());
            }
            target = target.toRealPath();                      // resolves symlinks
            if (!target.startsWith(root)) {
                throw new NoSuchFileException(file.getFilePath());
            }
        } catch (IOException | RuntimeException e) {
            LOG.log(Level.WARNING, "Stored file is missing or invalid for file id " + fileId);
            Errors.send(req, resp, 404, "The file is missing on the server.");
            return;
        }

        // Only whitelisted types may be shown inline; anything else is forced to download
        boolean inlineOk = INLINE_TYPES.contains(file.getMimeType());
        boolean forceDownload = "1".equals(req.getParameter("download"));
        String disposition = (inlineOk && !forceDownload) ? "inline" : "attachment";
        String asciiName = file.getOriginalFilename().replaceAll("[^A-Za-z0-9._-]", "_");
        String utf8Name = URLEncoder.encode(file.getOriginalFilename(), StandardCharsets.UTF_8).replace("+", "%20");

        resp.setContentType(inlineOk ? file.getMimeType() : "application/octet-stream");
        resp.setContentLengthLong(Files.size(target));
        resp.setHeader("Content-Disposition", disposition + "; filename=\"" + asciiName + "\"; filename*=UTF-8''" + utf8Name);
        resp.setHeader("X-Content-Type-Options", "nosniff");
        resp.setHeader("Content-Security-Policy", "default-src 'none'; style-src 'unsafe-inline'; sandbox");
        resp.setHeader("Cache-Control", "private, no-cache");

        // Stream in 8 KB pieces so large files never have to fit in memory
        try (InputStream in = Files.newInputStream(target);
             ServletOutputStream out = resp.getOutputStream()) {
            byte[] buffer = new byte[8192];
            int n;
            while ((n = in.read(buffer)) != -1) {
                out.write(buffer, 0, n);
            }
        } catch (IOException e) {
            // Usually the browser closed the connection early; nothing useful to tell the user
            LOG.log(Level.FINE, "Streaming interrupted for file " + fileId, e);
        }
    }
}
