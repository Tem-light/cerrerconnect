import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { jobAPI } from '../../utils/api';
import Sidebar from '../../components/Sidebar';
import Loader from '../../components/Loader';
import { Briefcase, Users, TrendingUp, Eye } from 'lucide-react';
import { Link } from 'react-router-dom';

const Dashboard = () => {
  const { user } = useAuth();
  const recruiterNotApproved = user?.role === 'recruiter' && user?.approved === false;
  const [loading, setLoading] = useState(true);
  const [stats, setStats] = useState({
    totalJobs: 0,
    activeJobs: 0,
    totalApplicants: 0,
    totalViews: 0,
  });
  const [recentJobs, setRecentJobs] = useState([]);

  useEffect(() => {
    if (recruiterNotApproved) {
      setLoading(false);
      return;
    }
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      const jobs = await jobAPI.getRecruiterJobs();
      const activeJobs = jobs.filter((job) => job.status === 'active');
      const totalApplicants = jobs.reduce((sum, job) => sum + (job.applicantsCount || 0), 0);

      setRecentJobs(jobs.slice(0, 3));
      setStats({
        totalJobs: jobs.length,
        activeJobs: activeJobs.length,
        totalApplicants,
        totalViews: jobs.length * 120,
      });
    } catch (error) {
      console.error('Error fetching dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return <Loader fullScreen />;
  }

  if (recruiterNotApproved) {
    return (
      <div className="flex min-h-screen bg-gray-50 dark:bg-gray-900">
        <Sidebar />
        <div className="flex-1 p-8">
          <div className="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-xl shadow-md p-8">
            <h1 className="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-2">Pending Approval</h1>
            <p className="text-gray-600 dark:text-gray-300">
              Your recruiter account is pending admin approval. Once approved, you’ll be able to post and manage jobs.
            </p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex min-h-screen bg-gray-50 dark:bg-gray-900">
      <Sidebar />
      <div className="flex-1 p-8">
        <div className="max-w-7xl mx-auto">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
              Welcome back, {user.name}!
            </h1>
            <p className="text-gray-600 dark:text-gray-300">Manage your job postings and applicants</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border-l-4 border-blue-600 dark:border-gray-700">
              <div className="flex items-center justify-between mb-4">
                <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <Briefcase className="w-6 h-6 text-blue-600" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-1">{stats.totalJobs}</h3>
              <p className="text-gray-600 dark:text-gray-300 text-sm">Total Jobs</p>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border-l-4 border-green-600 dark:border-gray-700">
              <div className="flex items-center justify-between mb-4">
                <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <TrendingUp className="w-6 h-6 text-green-600" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-1">{stats.activeJobs}</h3>
              <p className="text-gray-600 dark:text-gray-300 text-sm">Active Jobs</p>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border-l-4 border-teal-600 dark:border-gray-700">
              <div className="flex items-center justify-between mb-4">
                <div className="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center">
                  <Users className="w-6 h-6 text-teal-600" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-1">{stats.totalApplicants}</h3>
              <p className="text-gray-600 dark:text-gray-300 text-sm">Total Applicants</p>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 border-l-4 border-yellow-600 dark:border-gray-700">
              <div className="flex items-center justify-between mb-4">
                <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                  <Eye className="w-6 h-6 text-yellow-600" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-1">{stats.totalViews}</h3>
              <p className="text-gray-600 dark:text-gray-300 text-sm">Total Views</p>
            </div>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-2xl font-bold text-gray-800 dark:text-gray-100">Recent Job Postings</h2>
              <Link
                to="/recruiter/jobs"
                className="text-blue-600 font-semibold hover:text-blue-700"
              >
                View All
              </Link>
            </div>

            {recentJobs.length === 0 ? (
              <div className="text-center py-12">
                <Briefcase className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 className="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">No jobs posted yet</h3>
                <p className="text-gray-600 dark:text-gray-300 mb-6">Start by posting your first job opening</p>
                <Link
                  to="/recruiter/post-job"
                  className="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                >
                  Post a Job
                </Link>
              </div>
            ) : (
              <div className="space-y-4">
                  {recentJobs.map((job) => (
                    <div
                      key={job._id || job.id}
                    className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-blue-300 transition-colors dark:hover:border-blue-500"
                  >
                    <div className="flex justify-between items-start">
                      <div>
                        <h3 className="text-lg font-bold text-gray-800 dark:text-gray-100 mb-1">{job.title}</h3>
                        <p className="text-gray-600 dark:text-gray-300 mb-2">{job.location}</p>
                        <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                          <span className="flex items-center gap-1">
                            <Users className="w-4 h-4" />
                            {(job.applicantsCount || 0)} applicants
                          </span>
                          <span>•</span>
                          <span>{job.type}</span>
                          <span>•</span>
                          <span>Posted {new Date(job.createdAt).toLocaleDateString()}</span>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Link
                          to={`/recruiter/applicants/${job._id || job.id}`}
                          className="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                        >
                          View Applicants
                        </Link>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          
        </div>
      </div>
    </div>
  );
};

export default Dashboard;