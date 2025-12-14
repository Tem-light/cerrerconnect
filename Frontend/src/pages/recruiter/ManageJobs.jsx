import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext';
import { jobAPI } from '../../utils/api';
import Sidebar from '../../components/Sidebar';
import JobCard from '../../components/JobCard';
import Modal from '../../components/Modal';
import Toast from '../../components/Toast';
import Loader from '../../components/Loader';
import { Link } from 'react-router-dom';
import { Edit, Trash2, Users } from 'lucide-react';

const ManageJobs = () => {
  const { user } = useAuth();
  const [jobs, setJobs] = useState([]);
  const [loading, setLoading] = useState(true);

  // Delete modal state
  const [deleteModal, setDeleteModal] = useState(null);
  const [deleting, setDeleting] = useState(false);

  // Edit modal state
  const [editModal, setEditModal] = useState(null);
  const [updating, setUpdating] = useState(false);
  const [editForm, setEditForm] = useState({
    title: '',
    company: '',
    location: '',
    type: '',
    category: '',
    salaryMin: '',
    salaryMax: '',
    openings: 1,
    applicationStart: '',
    applicationEnd: '',
    contactEmail: '',
    contactWebsite: '',
    description: '',
    requirements: [],
  });

  const [toast, setToast] = useState(null);

  useEffect(() => {
    fetchJobs();
  }, []);

  const fetchJobs = async () => {
    try {
      const data = await jobAPI.getRecruiterJobs(user.id);
      setJobs(data);
    } catch (error) {
      console.error('Error fetching jobs:', error);
      setToast({ message: 'Failed to load jobs', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  // Delete Job
  const handleDelete = async () => {
    setDeleting(true);
    try {
      await jobAPI.deleteJob(deleteModal._id || deleteModal.id);
      setJobs(jobs.filter((job) => (job._id || job.id) !== (deleteModal._id || deleteModal.id)));
      setToast({ message: 'Job deleted successfully', type: 'success' });
      setDeleteModal(null);
    } catch (error) {
      setToast({ message: 'Failed to delete job', type: 'error' });
    } finally {
      setDeleting(false);
    }
  };

  // Open Edit Modal
  const handleEdit = (job) => {
    setEditForm({
      title: job.title,
      company: job.company,
      location: job.location,
      type: job.type,
      category: job.category,
      salaryMin: job.salaryMin,
      salaryMax: job.salaryMax,
      openings: job.openings || 1,
      applicationStart: job.applicationStart ? job.applicationStart.substring(0,10) : '',
      applicationEnd: job.applicationEnd ? job.applicationEnd.substring(0,10) : '',
      contactEmail: job.contactEmail || '',
      contactWebsite: job.contactWebsite || '',
      description: job.description,
      requirements: job.requirements || [],
    });
    setEditModal(job); // keep the job object with _id
  };

  // Handle Edit Form Change
  const handleEditChange = (e) => {
    setEditForm({ ...editForm, [e.target.name]: e.target.value });
  };

  // Submit Job Update
  const handleUpdate = async () => {
    setUpdating(true);
    try {
      const updatedJob = await jobAPI.updateJob(editModal._id, editForm);
      setJobs(jobs.map((job) => (job._id === editModal._id ? updatedJob : job)));
      setToast({ message: 'Job updated successfully', type: 'success' });
      setEditModal(null);
    } catch (error) {
      setToast({ message: 'Failed to update job', type: 'error' });
    } finally {
      setUpdating(false);
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
          <div className="mb-8 flex justify-between items-center">
            <div>
              <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Manage Jobs</h1>
              <p className="text-gray-600 dark:text-gray-300">View and manage all your job postings</p>
            </div>
            <Link
              to="/recruiter/post-job"
              className="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
            >
              Post New Job
            </Link>
          </div>

          {jobs.length === 0 ? (
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
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
            <div className="space-y-6">
              {jobs.map((job) => (
                <div
                  key={job._id || job.id}
                  className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow"
                >
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <h3 className="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">{job.title}</h3>
                      <p className="text-blue-600 font-semibold mb-2">{job.company}</p>
                      <div className="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <span>{job.location}</span>
                        <span>•</span>
                        <span>{job.type}</span>
                        <span>•</span>
                        <span>{job.salaryMin} - {job.salaryMax}</span>
                        <span>•</span>
                        <span>{(job.applicantsCount||0)} / {(job.openings||'-')} spots</span>
                      </div>
                    </div>
                    <span
                      className={`px-3 py-1 rounded-full text-xs font-semibold ${
                        job.status === 'active'
                          ? 'bg-green-100 text-green-700'
                          : 'bg-gray-100 text-gray-700'
                      }`}
                    >
                      {job.status}
                    </span>
                  </div>

                  <p className="text-gray-600 dark:text-gray-400 mb-4">{job.description}</p>

                  <div className="flex gap-3">
                    <Link
                      to={`/recruiter/applicants/${job._id}`}
                      className="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                      <Users className="w-4 h-4" />
                      View Applicants
                    </Link>
                    <button
                      onClick={() => handleEdit(job)}
                      className="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg font-semibold hover:bg-gray-200 transition-colors"
                    >
                      <Edit className="w-4 h-4" />
                      Edit
                    </button>
                    <button
                      onClick={() => setDeleteModal(job)}
                      className="flex items-center gap-2 bg-red-100 text-red-700 px-4 py-2 rounded-lg font-semibold hover:bg-red-200 transition-colors"
                    >
                      <Trash2 className="w-4 h-4" />
                      Delete
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Delete Modal */}
      <Modal
        isOpen={!!deleteModal}
        onClose={() => setDeleteModal(null)}
        title="Delete Job"
        size="sm"
      >
        <div className="space-y-6">
          <p className="text-gray-700 dark:text-gray-300">
            Are you sure you want to delete the job posting for{' '}
            <span className="font-bold">{deleteModal?.title}</span>? This action cannot be undone.
          </p>
          <div className="flex justify-end gap-4">
            <button
              onClick={() => setDeleteModal(null)}
              className="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors"
            >
              Cancel
            </button>
            <button
              onClick={handleDelete}
              disabled={deleting}
              className="px-6 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition-colors disabled:bg-red-400"
            >
              {deleting ? 'Deleting...' : 'Delete'}
            </button>
          </div>
        </div>
      </Modal>

      {/* Edit Modal */}
      <Modal
        isOpen={!!editModal}
        onClose={() => setEditModal(null)}
        title="Edit Job"
        size="lg"
      >
        <div className="space-y-4">
          {editModal && (
            <>
              {['title', 'company', 'location', 'type', 'category', 'description'].map((field) => (
                <div key={field}>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {field.charAt(0).toUpperCase() + field.slice(1)}
                  </label>
                  {field === 'description' ? (
                    <textarea
                      name={field}
                      value={editForm[field]}
                      onChange={handleEditChange}
                      rows="4"
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                    />
                  ) : (
                    <input
                      type="text"
                      name={field}
                      value={editForm[field]}
                      onChange={handleEditChange}
                      className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                    />
                  )}
                </div>
              ))}

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Salary Min</label>
                  <input type="number" name="salaryMin" value={editForm.salaryMin} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Salary Max</label>
                  <input type="number" name="salaryMax" value={editForm.salaryMax} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Openings</label>
                  <input type="number" name="openings" value={editForm.openings} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                  <input type="date" name="applicationStart" value={editForm.applicationStart} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                  <input type="date" name="applicationEnd" value={editForm.applicationEnd} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Email</label>
                  <input type="email" name="contactEmail" value={editForm.contactEmail} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company Website</label>
                  <input type="url" name="contactWebsite" value={editForm.contactWebsite} onChange={handleEditChange} className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300" />
                </div>
              </div>

              <div className="flex justify-end gap-4">
                <button
                  onClick={() => setEditModal(null)}
                  className="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  onClick={handleUpdate}
                  disabled={updating}
                  className="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-blue-400"
                >
                  {updating ? 'Updating...' : 'Update Job'}
                </button>
              </div>
            </>
          )}
        </div>
      </Modal>

      {toast && <Toast {...toast} onClose={() => setToast(null)} />}
    </div>
  );
};

export default ManageJobs;