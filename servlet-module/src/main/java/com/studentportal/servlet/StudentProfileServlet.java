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
import java.util.Optional;
import java.util.logging.Level;
import java.util.logging.Logger;

/** URL: /student?id=N - one profile with its files. Handles invalid ids, missing students and access rules. */
public class StudentProfileServlet extends HttpServlet {
    private static final long serialVersionUID = 1L;
    private static final Logger LOG = Logger.getLogger(StudentProfileServlet.class.getName());
    private final StudentDAO studentDAO = new StudentDAO();

    @Override
    protected void doGet(HttpServletRequest req, HttpServletResponse resp) throws ServletException, IOException {
        User user = (User) req.getSession().getAttribute(AuthFilter.SESSION_USER);

        int id;
        try {
            id = Integer.parseInt(req.getParameter("id"));          // null or "abc" -> exception
            if (id < 1) throw new NumberFormatException();
        } catch (NumberFormatException e) {
            Errors.send(req, resp, 400, "Invalid student id.");
            return;
        }
        if (!user.canAccessStudent(id)) {                           // students may only open their own page
            Errors.send(req, resp, 403, "You do not have permission to view this profile.");
            return;
        }
        try {
            Optional<Student> student = studentDAO.findByIdWithFiles(id);
            if (!student.isPresent()) {
                Errors.send(req, resp, 404, "Student not found.");
                return;
            }
            req.setAttribute("student", student.get());
            req.getRequestDispatcher("/student-profile.jsp").forward(req, resp);
        } catch (SQLException e) {
            LOG.log(Level.SEVERE, "Could not load student " + id, e);
            Errors.send(req, resp, 500, "The profile could not be loaded right now.");
        }
    }
}
