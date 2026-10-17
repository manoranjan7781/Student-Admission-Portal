package com.studentportal.util;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpSession;
import java.security.MessageDigest;
import java.security.SecureRandom;
import java.util.Base64;

/** Cross-Site Request Forgery protection: one random token per session, checked on every POST. */
public final class CsrfUtil {
    private static final String ATTR = "csrfToken";
    private static final SecureRandom RANDOM = new SecureRandom();

    private CsrfUtil() { }

    /** Returns the session's token, creating it on first use. */
    public static String getToken(HttpSession session) {
        String token = (String) session.getAttribute(ATTR);
        if (token == null) {
            byte[] bytes = new byte[32];
            RANDOM.nextBytes(bytes);
            token = Base64.getUrlEncoder().withoutPadding().encodeToString(bytes);
            session.setAttribute(ATTR, token);
        }
        return token;
    }

    /** True only when the posted "csrfToken" equals the session's token (constant-time compare). */
    public static boolean isValid(HttpServletRequest request) {
        HttpSession session = request.getSession(false);
        String expected = session == null ? null : (String) session.getAttribute(ATTR);
        String sent = request.getParameter("csrfToken");
        if (expected == null || sent == null) {
            return false;
        }
        return MessageDigest.isEqual(expected.getBytes(java.nio.charset.StandardCharsets.UTF_8),
                                     sent.getBytes(java.nio.charset.StandardCharsets.UTF_8));
    }
}
