<%@ page contentType="text/html;charset=UTF-8" language="java"
         import="java.util.List, com.studentportal.model.Student, com.studentportal.model.StudentFile" %>
<%-- Single profile page, filled by StudentProfileServlet (request attribute "student"). --%>
<%
    Student student = (Student) request.getAttribute("student");
    if (student == null) {
        response.sendRedirect(request.getContextPath() + "/students");
        return;
    }
    String pageTitle = student.getName();
    StudentFile photo = student.getProfileImage();
    List<StudentFile> docs = student.getDocuments();
%>
<%@ include file="/WEB-INF/jspf/header.jspf" %>
<div class="page-title">
  <h1><%= HtmlUtil.escape(student.getName()) %></h1>
  <% if (currentUser.isAdmin()) { %>
    <a class="btn secondary" href="<%= ctx %>/students">All students</a>
  <% } %>
</div>

<section class="card profile">
  <div>
    <% if (photo != null) { %>
      <img class="photo" src="<%= ctx %>/file?id=<%= photo.getId() %>" alt="Profile photo of <%= HtmlUtil.escape(student.getName()) %>">
    <% } else { %>
      <div class="photo avatar ph" role="img" aria-label="No profile photo uploaded" style="display:flex;border-radius:10px">No photo</div>
    <% } %>
  </div>
  <dl class="details">
    <dt>Roll number</dt><dd><%= HtmlUtil.escape(student.getRollNumber()) %></dd>
    <dt>Email</dt><dd><%= HtmlUtil.escape(student.getEmail()) %></dd>
    <dt>Course</dt><dd><%= HtmlUtil.escape(student.getCourse()) %></dd>
    <dt>Registered</dt><dd><%= HtmlUtil.escape(student.getCreatedAt()) %></dd>
  </dl>
</section>

<h2 style="margin-top:1.5rem">Uploaded files</h2>
<% if (student.getFiles().isEmpty()) { %>
  <p class="muted">No files uploaded for this student yet.</p>
<% } else { %>
  <div class="card table-wrap" style="padding:0">
    <table>
      <caption class="sr-only">Metadata of uploaded files</caption>
      <thead><tr><th>Type</th><th>Original name</th><th>MIME</th><th>Size</th><th>Uploaded</th></tr></thead>
      <tbody>
        <% for (StudentFile f : student.getFiles()) { %>
          <tr>
            <td><%= HtmlUtil.escape(f.getCategoryLabel()) %></td>
            <td><%= HtmlUtil.escape(f.getOriginalFilename()) %></td>
            <td><%= HtmlUtil.escape(f.getMimeType()) %></td>
            <td><%= HtmlUtil.formatBytes(f.getFileSize()) %></td>
            <td><%= HtmlUtil.escape(f.getUploadedAt()) %></td>
          </tr>
        <% } %>
      </tbody>
    </table>
  </div>

  <% if (!docs.isEmpty()) { %>
    <h3 style="margin-top:1.25rem">Documents</h3>
    <ul class="file-list">
      <% for (StudentFile f : docs) { %>
        <li>
          <strong><%= HtmlUtil.escape(f.getCategoryLabel()) %></strong>
          <% if (f.isImage()) { %>
            <img class="preview" src="<%= ctx %>/file?id=<%= f.getId() %>" alt="<%= HtmlUtil.escape(f.getCategoryLabel()) %>: <%= HtmlUtil.escape(f.getOriginalFilename()) %>">
          <% } %>
          <span>
            <a href="<%= ctx %>/file?id=<%= f.getId() %>" target="_blank" rel="noopener">Open <%= HtmlUtil.escape(f.getOriginalFilename()) %></a>
            &middot; <a href="<%= ctx %>/file?id=<%= f.getId() %>&amp;download=1">Download</a>
          </span>
        </li>
      <% } %>
    </ul>
  <% } %>
<% } %>
<%@ include file="/WEB-INF/jspf/footer.jspf" %>
