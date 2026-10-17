package com.studentportal.servlet;

import com.studentportal.util.CsrfUtil;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.HttpSession;
import java.io.IOException;

/** URL: /logout - POST only (with CSRF token). Destroys the server-side session. */
public class LogoutServlet extends HttpServlet {
    private static final long serialVersionUID = 1L;

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        if (CsrfUtil.isValid(req)) {
            HttpSession session = req.getSession(false);
            if (session != null) {
                session.invalidate();                 // removes the user and all session data
            }
            resp.sendRedirect(req.getContextPath() + "/login?loggedout=1");
        } else {
            resp.sendError(HttpServletResponse.SC_BAD_REQUEST, "Invalid request");
        }
    }

    /** A plain link/GET must not log anyone out. */
    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws IOException {
        resp.sendRedirect(req.getContextPath() + "/students");
    }
}
