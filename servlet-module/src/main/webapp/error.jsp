<%@ page contentType="text/html;charset=UTF-8" language="java" session="false" %>
<%--
  Generic error page (used by Errors.send() and by the <error-page> entries in web.xml).
  It never prints stack traces, exception messages or server paths.
--%>
<%
    Integer status = (Integer) request.getAttribute("errorStatus");
    if (status == null) status = (Integer) request.getAttribute("jakarta.servlet.error.status_code");
    if (status == null) status = 500;
    String message = (String) request.getAttribute("errorMessage");
    if (message == null) {
        switch (status) {
            case 400: message = "The request was not valid."; break;
            case 401: message = "Please log in first."; break;
            case 403: message = "You do not have permission to open this page."; break;
            case 404: message = "The page you asked for does not exist."; break;
            default:  message = "Something went wrong on the server. Please try again later.";
        }
    }
    if (response.getStatus() == 200) response.setStatus(status);
    String ctx = request.getContextPath();
%>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error <%= status %> | Admission Portal</title>
  <link rel="stylesheet" href="<%= ctx %>/assets/style.css">
</head>
<body>
<header class="site-header"><div class="bar"><a class="brand" href="<%= ctx %>/">Admission Portal &middot; Servlet</a></div></header>
<main class="container">
  <div class="card narrow">
    <h1><%= status %></h1>
    <p><%= com.studentportal.util.HtmlUtil.escape(message) %></p>
    <p><a class="btn secondary" href="<%= ctx %>/">Back to home</a></p>
  </div>
</main>
</body>
</html>
