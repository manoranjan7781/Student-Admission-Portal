<%@ page contentType="text/html;charset=UTF-8" language="java" %>
<%-- Login form. The LoginServlet processes the POST. --%>
<% String pageTitle = "Login"; %>
<%@ include file="/WEB-INF/jspf/header.jspf" %>
<%
    // Message from the servlet (wrong password, DB error ...) or from a redirect parameter
    String loginError = (String) request.getAttribute("loginError");
    if (loginError == null && "1".equals(request.getParameter("unauthorized"))) {
        loginError = "Please log in to continue.";
    }
    String entered = request.getAttribute("enteredUsername") == null ? "" : (String) request.getAttribute("enteredUsername");
%>
<div class="card narrow">
  <h1>Log in</h1>
  <p class="muted">Use the same account as the PHP module (admin, or a student's email).</p>

  <% if ("1".equals(request.getParameter("loggedout"))) { %>
    <div class="alert success" role="status">You have been logged out.</div>
  <% } %>
  <% if (loginError != null) { %>
    <div class="alert error" role="alert"><%= HtmlUtil.escape(loginError) %></div>
  <% } %>

  <form method="post" action="<%= ctx %>/login">
    <input type="hidden" name="csrfToken" value="<%= CsrfUtil.getToken(session) %>">
    <div class="form-grid">
      <div class="field">
        <label for="username">Username or email</label>
        <input type="text" id="username" name="username" value="<%= HtmlUtil.escape(entered) %>" autocomplete="username" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required>
      </div>
    </div>
    <div class="btn-row"><button class="btn" type="submit">Log in</button></div>
  </form>
</div>
<%@ include file="/WEB-INF/jspf/footer.jspf" %>
