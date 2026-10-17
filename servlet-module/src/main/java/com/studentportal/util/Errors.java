package com.studentportal.util;

import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;

/** Sends a friendly error page. Never includes stack traces or internal paths. */
public final class Errors {
    private Errors() { }

    public static void send(HttpServletRequest req, HttpServletResponse resp, int status, String message)
            throws ServletException, IOException {
        resp.setStatus(status);
        req.setAttribute("errorStatus", status);
        req.setAttribute("errorMessage", message);
        req.getRequestDispatcher("/error.jsp").forward(req, resp);
    }
}
