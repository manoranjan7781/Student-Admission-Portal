package com.studentportal.servlet;

import com.studentportal.dao.StudentDAO;
import com.studentportal.filter.AuthFilter;
import com.studentportal.model.Student;
import com.studentportal.model.User;
import com.studentportal.util.Errors;
import jakarta.servlet.ServletException;
import jakarta.servlet.http.HttpServlet;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import java.io.IOException;
import java.sql.SQLException;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

/** URL: /students - admins see every student; students are sent to their own profile. */
public class StudentListServlet extends HttpServlet {
    private static final long serialVersionUID = 1L;
    private static final Logger LOG = Logger.getLogger(StudentListServlet.class.getName());
    private final StudentDAO studentDAO = new StudentDAO();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        User user = (User) req.getSession().getAttribute(AuthFilter.SESSION_USER);   // filter guarantees it exists

        if (!user.isAdmin()) {
            // A student has no list: go to the one profile they may see
            resp.sendRedirect(req.getContextPath() + "/student?id=" + user.getStudentId());
            return;
        }
        try {
            List<Student> students = studentDAO.findAllWithFiles();
            req.setAttribute("students", students);                         // the JSP loops over this list
            req.getRequestDispatcher("/students.jsp").forward(req, resp);
        } catch (SQLException e) {
            LOG.log(Level.SEVERE, "Could not load students", e);
            Errors.send(req, resp, 500, "The student list could not be loaded right now.");
        }
    }
}
