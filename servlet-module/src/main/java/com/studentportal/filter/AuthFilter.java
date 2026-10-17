package com.studentportal.filter;

import jakarta.servlet.Filter;
import jakarta.servlet.FilterChain;
import jakarta.servlet.ServletException;
import jakarta.servlet.ServletRequest;
import jakarta.servlet.ServletResponse;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.servlet.http.HttpSession;
import java.io.IOException;

/**
 * Runs before EVERY request (mapped to /* in web.xml).
 * Public: the login page, CSS/JS assets and the error page.
 * Everything else, including the file-streaming servlet, needs a logged-in session.
 */
public class AuthFilter implements Filter {

    /** Session attribute that holds the com.studentportal.model.User. */
    public static final String SESSION_USER = "user";

    @Override
    public void doFilter(ServletRequest req, ServletResponse res, FilterChain chain)
            throws IOException, ServletException {
        HttpServletRequest request = (HttpServletRequest) req;
        HttpServletResponse response = (HttpServletResponse) res;

        // Path inside the web application, e.g. "/students" (context path removed)
        String path = request.getRequestURI().substring(request.getContextPath().length());

        // Security headers for every response
        response.setHeader("X-Content-Type-Options", "nosniff");
        response.setHeader("X-Frame-Options", "DENY");
        response.setHeader("Referrer-Policy", "same-origin");

        if (isPublic(path)) {
            chain.doFilter(req, res);
            return;
        }

        HttpSession session = request.getSession(false);      // false = do not create a session
        boolean loggedIn = session != null && session.getAttribute(SESSION_USER) != null;

        if (!loggedIn) {
            if (path.startsWith("/file")) {
                // An <img> tag cannot follow a login page, so answer with a plain 401
                response.sendError(HttpServletResponse.SC_UNAUTHORIZED, "Login required");
            } else {
                response.sendRedirect(request.getContextPath() + "/login?unauthorized=1");
            }
            return;
        }

        // Pages with personal data must not be stored by the browser (Back button after logout)
        if (!path.startsWith("/file")) {
            response.setHeader("Cache-Control", "no-store, no-cache, must-revalidate");
            response.setHeader("Pragma", "no-cache");
        }
        chain.doFilter(req, res);
    }

    private boolean isPublic(String path) {
        return path.equals("/login") || path.equals("/login.jsp") || path.equals("/error.jsp")
            || path.startsWith("/assets/");
    }
}
