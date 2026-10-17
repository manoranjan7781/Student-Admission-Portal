package com.studentportal.servlet;

import com.studentportal.dao.UserDAO;
import com.studentportal.filter.AuthFilter;
import com.studentportal.model.User;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.HttpSession;
import java.io.IOException;
import java.sql.SQLException;
import java.util.Optional;
import java.util.logging.Level;
import java.util.logging.Logger;

/** URL: /login  - GET shows the form, POST checks the credentials and creates the session. */
public class LoginServlet extends HttpServlet {
    private static final long serialVersionUID = 1L;
    private static final Logger LOG = Logger.getLogger(LoginServlet.class.getName());
    private final UserDAO userDAO = new UserDAO();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        HttpSession session = req.getSession(false);
        if (session != null && session.getAttribute(AuthFilter.SESSION_USER) != null) {
            resp.sendRedirect(req.getContextPath() + "/students");   // already logged in
            return;
        }
        req.getRequestDispatcher("/login.jsp").forward(req, resp);
    }

    @Override
    protected void doPost(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        req.setCharacterEncoding("UTF-8");
        if (!com.studentportal.util.CsrfUtil.isValid(req)) {
            fail(req, resp, 400, "Your form expired. Please try again.");
            return;
        }
        String username = trim(req.getParameter("username"));
        String password = req.getParameter("password") == null ? "" : req.getParameter("password");
        if (username.isEmpty() || password.isEmpty() || username.length() > 150 || password.length() > 200) {
            fail(req, resp, 401, "Invalid username or password.");
            return;
        }

        try {
            Optional<User> user = userDAO.authenticate(username, password);
            if (!user.isPresent()) {
                fail(req, resp, 401, "Invalid username or password.");      // same message for every failure
                return;
            }
            // Login OK. Give the user a NEW session id (prevents session fixation), then store the user.
            HttpSession session = req.getSession(true);
            req.changeSessionId();
            session.setAttribute(AuthFilter.SESSION_USER, user.get());
            resp.sendRedirect(req.getContextPath() + "/students");
        } catch (SQLException e) {
            LOG.log(Level.SEVERE, "Login failed because of a database error", e);   // details only in the server log
            fail(req, resp, 500, "The service is temporarily unavailable. Please try again later.");
        }
    }

    private void fail(HttpServletRequest req, HttpServletResponse resp, int status, String message)
            throws ServletException, IOException {
        resp.setStatus(status);
        req.setAttribute("loginError", message);
        req.setAttribute("enteredUsername", trim(req.getParameter("username")));
        req.getRequestDispatcher("/login.jsp").forward(req, resp);
    }

    private static String trim(String s) { return s == null ? "" : s.trim(); }
}
