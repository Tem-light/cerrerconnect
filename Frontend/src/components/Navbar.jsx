import { Link, useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';
import { Briefcase, LogOut, User, Menu, X, Moon, Sun } from 'lucide-react';
import { useState } from 'react';
import NotificationBell from './NotificationBell';
import { useTheme } from '../context/ThemeContext';

const Navbar = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const { mode, setMode } = useTheme();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const getDashboardLink = () => {
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

  const avatarSrc = user?.role === 'student' ? user?.profile?.avatarUrl : user?.role === 'recruiter' ? user?.profile?.logoUrl : '';
  const safeAvatarSrc = avatarSrc && avatarSrc.startsWith('/uploads') ? `${avatarSrc}` : avatarSrc; // supports vite proxy for /uploads

return (
  <nav className="bg-white dark:bg-gray-700 shadow-md sticky top-0 z-50">
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div className="flex justify-between items-center h-16">
        <Link to={getDashboardLink()} className="flex items-center gap-2">
          <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
            <Briefcase className="w-6 h-6 text-white" />
          </div>
          <span className="text-xl font-bold text-gray-800 dark:text-gray-200">CareerConnect</span>
        </Link>

        <div className="hidden md:flex items-center gap-6">
          {!user ? (
            <>
              <Link to="/" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium">
                Home
              </Link>
              <Link to="/about" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium">
                About
              </Link>
              <Link to="/contact" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium">
                Contact
              </Link>
              <Link to="/login" className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium">
                Login
              </Link>
              <Link
                to="/register"
                className="bg-blue-600 dark:text-white text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
              >
                Register
              </Link>
            </>
          ) : (
            <>
              {user.role === 'student' && (
                <>
                  <Link
                    to="/student/dashboard"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Dashboard
                  </Link>
                  <Link
                    to="/student/jobs"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Find Jobs
                  </Link>
                  <Link
                    to="/student/applied"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Applications
                  </Link>
                </>
              )}

              {user.role === 'recruiter' && (
                <>
                  <Link
                    to="/recruiter/dashboard"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Dashboard
                  </Link>
                  <Link
                    to="/recruiter/post-job"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Post Job
                  </Link>
                  <Link
                    to="/recruiter/jobs"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Manage Jobs
                  </Link>
                </>
              )}

              {user.role === 'admin' && (
                <>
                  <Link
                    to="/admin/dashboard"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Dashboard
                  </Link>
                  <Link
                    to="/admin/users"
                    className="text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium"
                  >
                    Users
                  </Link>
                </>
              )}

              <div className="flex items-center gap-4">
                <button
                  onClick={() => setMode(mode === 'dark' ? 'light' : 'dark')}
                  className="text-gray-700 dark:text-gray-300 hover:text-blue-600"
                  title={`Switch to ${mode === 'dark' ? 'light' : 'dark'} mode`}
                >
                  {mode === 'dark' ? <Sun className="w-5 h-5" /> : <Moon className="w-5 h-5" />}
                </button>
                <NotificationBell />
                <Link
                  to={user.role === 'student' ? '/student/profile' : '#'}
                  className="flex items-center gap-2 text-gray-700 dark:text-gray-300 hover:text-blue-600"
                >
                  <div className="w-8 h-8 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center">
                    {safeAvatarSrc ? (
                      <img src={safeAvatarSrc} alt={user.name} className="w-full h-full object-cover" />
                    ) : (
                      <User className="w-5 h-5" />
                    )}
                  </div>
                  <span className="font-medium">{user.name}</span>
                </Link>
                <button
                  onClick={handleLogout}
                  className="flex items-center gap-2 text-red-600 hover:text-red-700 font-medium"
                >
                  <LogOut className="w-5 h-5" />
                  Logout
                </button>
              </div>
            </>
          )}
        </div>

        <button
          onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          className="md:hidden text-gray-700"
        >
          {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>
    </div>

    {mobileMenuOpen && (
      <div className="md:hidden bg-white border-t">
        <div className="px-4 py-4 space-y-3">
          {!user ? (
            <>
              <Link
                to="/"
                className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                onClick={() => setMobileMenuOpen(false)}
              >
                Home
              </Link>
              <Link
                to="/about"
                className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                onClick={() => setMobileMenuOpen(false)}
              >
                About
              </Link>
              <Link
                to="/contact"
                className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                onClick={() => setMobileMenuOpen(false)}
              >
                Contact
              </Link>
              <Link
                to="/login"
                className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                onClick={() => setMobileMenuOpen(false)}
              >
                Login
              </Link>
              <Link
                to="/register"
                className="block bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700 text-center"
                onClick={() => setMobileMenuOpen(false)}
              >
                Register
              </Link>
            </>
          ) : (
            <>
              <div className="border-b pb-3 mb-3">
                <p className="text-sm text-gray-500 dark:text-gray-400">Signed in as</p>
                <p className="font-semibold text-gray-800 dark:text-gray-200">{user.name}</p>
              </div>
              {user.role === 'student' && (
                <>
                  <Link
                    to="/student/dashboard"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Dashboard
                  </Link>
                  <Link
                    to="/student/jobs"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Find Jobs
                  </Link>
                  <Link
                    to="/student/applied"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Applications
                  </Link>
                  <Link
                    to="/student/profile"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Profile
                  </Link>
                </>
              )}
              {user.role === 'recruiter' && (
                <>
                  <Link
                    to="/recruiter/dashboard"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Dashboard
                  </Link>
                  <Link
                    to="/recruiter/post-job"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Post Job
                  </Link>
                  <Link
                    to="/recruiter/jobs"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Manage Jobs
                  </Link>
                </>
              )}
              {user.role === 'admin' && (
                <>
                  <Link
                    to="/admin/dashboard"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Dashboard
                  </Link>
                  <Link
                    to="/admin/users"
                    className="block text-gray-700 dark:text-gray-300 hover:text-blue-600 font-medium py-2"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Users
                  </Link>
                </>
              )}
              <button
                onClick={() => {
                  handleLogout();
                  setMobileMenuOpen(false);
                }}
                className="block w-full text-left text-red-600 hover:text-red-700 font-medium py-2"
              >
                Logout
              </button>
            </>
          )}
        </div>
      </div>
    )}
  </nav>
);
};

export default Navbar;
