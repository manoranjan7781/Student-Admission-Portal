<%@ page contentType="text/html;charset=UTF-8" language="java" %>
<%-- Home page (welcome file). AuthFilter already guarantees the user is logged in. --%>
<% String pageTitle = "Home"; %>
<%@ include file="/WEB-INF/jspf/header.jspf" %>
<div class="card">
  <h1>Welcome, <%= HtmlUtil.escape(currentUser.getUsername()) %></h1>
  <%-- Conditional content depending on the role --%>
  <% if (currentUser.isAdmin()) { %>
    <p>You are signed in as an <strong>administrator</strong>. You can browse every student profile and the files they uploaded.</p>
    <div class="btn-row"><a class="btn" href="<%= ctx %>/students">View all students</a></div>
  <% } else { %>
    <p>You are signed in as a <strong>student</strong>. You can see your own profile and documents.</p>
    <div class="btn-row"><a class="btn" href="<%= ctx %>/student?id=<%= currentUser.getStudentId() %>">Open my profile</a></div>
  <% } %>
</div>
<%@ include file="/WEB-INF/jspf/footer.jspf" %>
