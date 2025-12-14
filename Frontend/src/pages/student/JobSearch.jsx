import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { jobAPI, applicationAPI } from '../../utils/api';
import Sidebar from '../../components/Sidebar';
import JobCard from '../../components/JobCard';
import Modal from '../../components/Modal';
import Toast from '../../components/Toast';
import Loader from '../../components/Loader';
import { Search, MapPin, Briefcase, Filter } from 'lucide-react';

const JobSearch = () => {
  const { user } = useAuth();
  const [jobs, setJobs] = useState([]);
  const [filteredJobs, setFilteredJobs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    search: '',
    location: '',
    category: '',
    type: '',
  });
  const [selectedJob, setSelectedJob] = useState(null);
  const [showApplyModal, setShowApplyModal] = useState(false);
  const [coverLetter, setCoverLetter] = useState('');
  const [applying, setApplying] = useState(false);
  const [toast, setToast] = useState(null);

  useEffect(() => {
    fetchJobs();
  }, []);

  useEffect(() => {
    applyFilters();
  }, [filters, jobs]);

  const fetchJobs = async () => {
    try {
      const data = await jobAPI.getAllJobs();
      setJobs(data);
      setFilteredJobs(data);
    } catch (error) {
      console.error('Error fetching jobs:', error);
      setToast({ message: 'Failed to load jobs', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const applyFilters = () => {
    let result = [...jobs];

    if (filters.search) {
      result = result.filter(
        (job) =>
          job.title.toLowerCase().includes(filters.search.toLowerCase()) ||
          job.company.toLowerCase().includes(filters.search.toLowerCase())
      );
    }

    if (filters.location) {
      result = result.filter((job) =>
        job.location.toLowerCase().includes(filters.location.toLowerCase())
      );
    }

    if (filters.category) {
      result = result.filter((job) => job.category === filters.category);
    }

    if (filters.type) {
      result = result.filter((job) => job.type === filters.type);
    }

    setFilteredJobs(result);
  };

  const handleFilterChange = (e) => {
    setFilters({ ...filters, [e.target.name]: e.target.value });
  };

  const handleApply = (job) => {
    setSelectedJob(job);
    setShowApplyModal(true);
  };

  const submitApplication = async () => {
    const token = localStorage.getItem('token');
    if (!token) {
      setToast({ 
        message: 'Please log in to apply for jobs', 
        type: 'error' 
      });
      return;
    }

    setApplying(true);
    try {
      const applicationData = {
        coverLetter,
      };
      
      await applicationAPI.applyForJob(selectedJob._id, applicationData);
      setToast({ 
        message: 'Application submitted successfully!', 
        type: 'success' 
      });
      setShowApplyModal(false);
      setCoverLetter('');
    } catch (error) {
      console.error('Application submission error:', error);
      const msg = error?.message || error?.data?.message || error?.response?.data?.message || 'Failed to submit application';
      if (error.response?.status === 401) {
        setToast({ message: 'Session expired. Please log in again.', type: 'error' });
        localStorage.removeItem('token');
      } else {
        setToast({ message: msg, type: 'error' });
      }
    } finally {
      setApplying(false);
    }
  };

  if (loading) {
    return <Loader fullScreen />;
  }

  return (
    <div className="flex min-h-screen bg-gray-50 dark:bg-gray-900">
      <Sidebar />
      <div className="flex-1 p-8">
        <div className="max-w-7xl mx-auto">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Find Your Dream Job</h1>
            <p className="text-gray-600 dark:text-gray-300">Browse through {jobs.length} available opportunities</p>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8 border border-gray-200 dark:border-gray-700">
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div className="md:col-span-2">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    name="search"
                    value={filters.search}
                    onChange={handleFilterChange}
                    placeholder="Search by job title or company"
                    className="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  />
                </div>
              </div>

              <div>
                <div className="relative">
                  <MapPin className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    name="location"
                    value={filters.location}
                    onChange={handleFilterChange}
                    placeholder="Location"
                    className="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  />
                </div>
              </div>

              <div>
                <div className="relative">
                  <Filter className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <select
                    name="type"
                    value={filters.type}
                    onChange={handleFilterChange}
                    className="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  >
                    <option value="">All Types</option>
                    <option value="Internship">Internship</option>
                    <option value="Full-time">Full-time</option>
                  </select>
                </div>
              </div>

              <div className="md:col-span-4">
                <div className="relative">
                  <Briefcase className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <select
                    name="category"
                    value={filters.category}
                    onChange={handleFilterChange}
                    className="w-full pl-11 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  >
                    <option value="">All Categories</option>
                    <option value="Software Development">Software Development</option>
                    <option value="Data Science">Data Science</option>
                    <option value="Design">Design</option>
                    <option value="Mobile Development">Mobile Development</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div className="mb-4 text-gray-600 dark:text-gray-300">
            Showing {filteredJobs.length} job{filteredJobs.length !== 1 ? 's' : ''}
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredJobs.map((job) => (
              <JobCard key={job._id} job={job} onApply={handleApply} />
            ))}
          </div>

          {filteredJobs.length === 0 && (
            <div className="text-center py-12">
              <p className="text-gray-600 dark:text-gray-400 text-lg">No jobs found matching your criteria</p>
            </div>
          )}
        </div>
      </div>

      <Modal
        isOpen={showApplyModal}
        onClose={() => setShowApplyModal(false)}
        title="Apply for Position"
      >
        {selectedJob && (
          <div className="space-y-6">
            <div>
              <h3 className="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">{selectedJob.title}</h3>
              <p className="text-blue-600 font-semibold">{selectedJob.company}</p>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-300">
              <div><strong>Location:</strong> {selectedJob.location}</div>
              <div><strong>Type:</strong> {selectedJob.type}</div>
              <div><strong>Salary:</strong> {selectedJob.salaryMin} - {selectedJob.salaryMax}</div>
              <div><strong>Openings:</strong> {selectedJob.openings || '-'}</div>
              <div><strong>Apply From:</strong> {selectedJob.applicationStart ? new Date(selectedJob.applicationStart).toLocaleDateString() : '—'}</div>
              <div><strong>Apply Until:</strong> {selectedJob.applicationEnd ? new Date(selectedJob.applicationEnd).toLocaleDateString() : '—'}</div>
              {selectedJob.contactEmail && (
                <div><strong>Contact Email:</strong> <a className="text-blue-600 hover:underline" href={`mailto:${selectedJob.contactEmail}`}>{selectedJob.contactEmail}</a></div>
              )}
              {selectedJob.contactWebsite && (
                <div><strong>Website:</strong> <a className="text-blue-600 hover:underline" href={selectedJob.contactWebsite} target="_blank" rel="noreferrer">{selectedJob.contactWebsite}</a></div>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Cover Letter
              </label>
              <textarea
                value={coverLetter}
                onChange={(e) => setCoverLetter(e.target.value)}
                rows="6"
                placeholder="Tell us why you're a great fit for this position..."
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
              />
            </div>

            <div className="flex justify-end gap-4">
              <button
                onClick={() => setShowApplyModal(false)}
                className="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={submitApplication}
                disabled={applying || !coverLetter.trim()}
                className="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-blue-400"
              >
                {applying ? 'Submitting...' : 'Submit Application'}
              </button>
            </div>
          </div>
        )}
      </Modal>

      {toast && <Toast {...toast} onClose={() => setToast(null)} />}
    </div>
  );
};

export default JobSearch;