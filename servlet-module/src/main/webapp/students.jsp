<%@ page contentType="text/html;charset=UTF-8" language="java"
         import="java.util.List, com.studentportal.model.Student, com.studentportal.model.StudentFile" %>
<%--
  Student listing (admin). Data is prepared by StudentListServlet and handed over as the
  request attribute "students". Opening this JSP directly (no data) redirects to the servlet.
--%>
<%
    @SuppressWarnings("unchecked")
    List<Student> students = (List<Student>) request.getAttribute("students");
    if (students == null) {
        response.sendRedirect(request.getContextPath() + "/students");
        return;
    }
    String pageTitle = "Students";
%>
<%@ include file="/WEB-INF/jspf/header.jspf" %>
<div class="page-title">
  <h1>Students <span class="muted">(<%= students.size() %>)</span></h1>
</div>

<%-- CONDITIONAL: empty list message --%>
<% if (students.isEmpty()) { %>
  <div class="card"><p class="muted">No students have been registered yet.</p></div>
<% } else { %>
  <div class="cards">
    <%-- LOOP: one card per student --%>
    <% for (Student s : students) {
           StudentFile photo = s.getProfileImage();     // may be null
           List<StudentFile> docs = s.getDocuments();
    %>
      <article class="card">
        <div class="student-card">
          <% if (photo != null) { %>
            <%-- The image bytes are streamed by FileServlet (/file?id=...) after an access check --%>
            <img class="avatar" src="<%= ctx %>/file?id=<%= photo.getId() %>" alt="Photo of <%= HtmlUtil.escape(s.getName()) %>">
          <% } else { %>
            <span class="avatar ph" aria-hidden="true"><%= HtmlUtil.escape(s.getName().isEmpty() ? "?" : s.getName().substring(0, 1).toUpperCase()) %></span>
          <% } %>
          <div>
            <h2 style="margin:0"><a href="<%= ctx %>/student?id=<%= s.getId() %>"><%= HtmlUtil.escape(s.getName()) %></a></h2>
            <div class="muted"><%= HtmlUtil.escape(s.getRollNumber()) %> &middot; <%= HtmlUtil.escape(s.getEmail()) %></div>
            <div><%= HtmlUtil.escape(s.getCourse()) %></div>
          </div>
        </div>
        <p style="margin-bottom:0">
          <%-- CONDITIONAL: handle students without uploaded documents --%>
          <% if (docs.isEmpty()) { %>
            <span class="muted">No documents uploaded.</span>
          <% } else { %>
            <strong>Documents:</strong>
            <% for (StudentFile f : docs) { %>
              <a href="<%= ctx %>/file?id=<%= f.getId() %>" target="_blank" rel="noopener"><%= HtmlUtil.escape(f.getCategoryLabel()) %></a>&nbsp;
            <% } %>
          <% } %>
        </p>
      </article>
    <% } %>
  </div>
<% } %>
<%@ include file="/WEB-INF/jspf/footer.jspf" %>
