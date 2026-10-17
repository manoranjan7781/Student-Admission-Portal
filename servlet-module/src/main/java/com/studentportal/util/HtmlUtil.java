package com.studentportal.util;

/** Small output helpers used by the JSP pages. */
public final class HtmlUtil {
    private HtmlUtil() { }

    /**
     * Escapes text for safe output in HTML (prevents XSS). JSP scriptlets do NOT
     * escape automatically, so every database value is printed through this method.
     */
    public static String escape(Object value) {
        if (value == null) {
            return "";
        }
        String s = value.toString();
        StringBuilder out = new StringBuilder(s.length() + 16);
        for (int i = 0; i < s.length(); i++) {
            char c = s.charAt(i);
            switch (c) {
                case '&':  out.append("&amp;"); break;
                case '<':  out.append("&lt;"); break;
                case '>':  out.append("&gt;"); break;
                case '"':  out.append("&quot;"); break;
                case '\'': out.append("&#39;"); break;
                default:   out.append(c);
            }
        }
        return out.toString();
    }

    /** 1536 -> "1.5 KB", 3145728 -> "3.0 MB" */
    public static String formatBytes(long bytes) {
        if (bytes >= 1048576L) {
            return String.format("%.1f MB", bytes / 1048576.0);
        }
        return String.format("%.1f KB", Math.max(bytes, 1) / 1024.0);
    }
}
