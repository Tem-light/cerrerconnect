import { Link, useLocation } from 'react-router-dom';
import { LayoutDashboard, Briefcase, FileText, User, Users, PlusCircle } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const Sidebar = () => {
  const location = useLocation();
  const { user } = useAuth();

  const isActive = (path) => location.pathname === path;

  const studentLinks = [
    { path: '/student/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { path: '/student/jobs', label: 'Find Jobs', icon: Briefcase },
    { path: '/student/applied', label: 'Applications', icon: FileText },
    { path: '/student/profile', label: 'Profile', icon: User },
  ];

  const recruiterLinks = [
    { path: '/recruiter/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { path: '/recruiter/post-job', label: 'Post Job', icon: PlusCircle },
    { path: '/recruiter/jobs', label: 'Manage Jobs', icon: Briefcase },
  ];

  const adminLinks = [
    { path: '/admin/dashboard', label: 'Dashboard', icon: LayoutDashboard },
    { path: '/admin/users', label: 'Manage Users', icon: Users },
  ];

  const getLinks = () => {
    switch (user?.role) {
      case 'student':
        return studentLinks;
      case 'recruiter':
        return recruiterLinks;
      case 'admin':
        return adminLinks;
      default:
        return [];
    }
  };

  const links = getLinks();

  return (
    <aside className="w-64 bg-white dark:bg-gray-800 shadow-lg h-screen sticky top-16">
      <nav className="p-4 space-y-2">
        {links.map(({ path, label, icon: Icon }) => (
          <Link
            key={path}
            to={path}
            className={`flex items-center gap-3 px-4 py-3 rounded-lg transition-all ${
              isActive(path)
                ? 'bg-blue-600 text-white shadow-md'
                : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800'
            }`}
          >
            <Icon className="w-5 h-5" />
            <span className="font-medium">{label}</span>
          </Link>
        ))}
      </nav>
    </aside>
  );
};

export default Sidebar;
