import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

import ProtectedRoute from '../components/ProtectedRoute';
import Navbar from '../components/Navbar';

import Home from '../pages/Home';
import About from '../pages/About';
import Contact from '../pages/Contact';
import NotFound from '../pages/NotFound';

import Login from '../pages/auth/Login';
import Register from '../pages/auth/Register';

import StudentDashboard from '../pages/student/Dashboard';
import StudentProfile from '../pages/student/Profile';
import StudentJobSearch from '../pages/student/JobSearch';
import StudentAppliedJobs from '../pages/student/AppliedJobs';

import RecruiterDashboard from '../pages/recruiter/Dashboard';
import RecruiterPostJob from '../pages/recruiter/PostJob';
import RecruiterManageJobs from '../pages/recruiter/ManageJobs';
import RecruiterApplicants from '../pages/recruiter/Applicants';

import AdminDashboard from '../pages/admin/Dashboard';
import AdminUsers from '../pages/admin/Users';

const AppRoutes = () => {
  const { user } = useAuth();

  const getDefaultRoute = () => {
    if (!user) return '/';
    switch (user.role) {
      case 'student':
        return '/student/dashboard';
      case 'recruiter':
        return '/recruiter/dashboard';
      case 'admin':
        return '/admin/dashboard';
      default:
        return '/';
    }
  };

  return (
    <>
      {!['/login', '/register'].includes(window.location.pathname) && <Navbar />}
      <Routes>
        <Route
          path="/"
          element={user ? <Navigate to={getDefaultRoute()} replace /> : <Home />}
        />
        <Route path="/about" element={<About />} />
        <Route path="/contact" element={<Contact />} />
        <Route
          path="/login"
          element={user ? <Navigate to={getDefaultRoute()} replace /> : <Login />}
        />
        <Route
          path="/register"
          element={user ? <Navigate to={getDefaultRoute()} replace /> : <Register />}
        />

        <Route
          path="/student/dashboard"
          element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/student/profile"
          element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentProfile />
            </ProtectedRoute>
          }
        />
        <Route
          path="/student/jobs"
          element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentJobSearch />
            </ProtectedRoute>
          }
        />
        <Route
          path="/student/applied"
          element={
            <ProtectedRoute allowedRoles={['student']}>
              <StudentAppliedJobs />
            </ProtectedRoute>
          }
        />

        <Route
          path="/recruiter/dashboard"
          element={
            <ProtectedRoute allowedRoles={['recruiter']}>
              <RecruiterDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/recruiter/post-job"
          element={
            <ProtectedRoute allowedRoles={['recruiter']}>
              <RecruiterPostJob />
            </ProtectedRoute>
          }
        />
        <Route
          path="/recruiter/jobs"
          element={
            <ProtectedRoute allowedRoles={['recruiter']}>
              <RecruiterManageJobs />
            </ProtectedRoute>
          }
        />
        <Route
          path="/recruiter/applicants/:jobId"
          element={
            <ProtectedRoute allowedRoles={['recruiter']}>
              <RecruiterApplicants />
            </ProtectedRoute>
          }
        />

        <Route
          path="/admin/dashboard"
          element={
            <ProtectedRoute allowedRoles={['admin']}>
              <AdminDashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin/users"
          element={
            <ProtectedRoute allowedRoles={['admin']}>
              <AdminUsers />
            </ProtectedRoute>
          }
        />

        <Route path="*" element={<NotFound />} />
      </Routes>
    </>
  );
};

export default AppRoutes;
