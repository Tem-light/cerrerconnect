import { useState, useEffect } from 'react';
import { userAPI } from '../../utils/api';
import Sidebar from '../../components/Sidebar';
import Loader from '../../components/Loader';
import Toast from '../../components/Toast';
import Modal from '../../components/Modal';
import { User, UserCheck, UserX, CheckCircle, XCircle, Mail, Building } from 'lucide-react';

const Users = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState('all');
  const [selectedUser, setSelectedUser] = useState(null);
  const [toast, setToast] = useState(null);

  useEffect(() => {
    fetchUsers();
  }, []);

  const fetchUsers = async () => {
    try {
      const data = await userAPI.getAllUsers();
      setUsers(data);
    } catch (error) {
      console.error('Error fetching users:', error);
      setToast({ message: 'Failed to load users', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (userId) => {
    try {
      await userAPI.approveRecruiter(userId);
      setUsers(users.map((user) => (user._id === userId ? { ...user, approved: true } : user)));
      setToast({ message: 'Recruiter approved successfully', type: 'success' });
      setSelectedUser(null);
    } catch (error) {
      setToast({ message: 'Failed to approve recruiter', type: 'error' });
    }
  };

  const handleBlock = async (userId) => {
    try {
      await userAPI.blockUser(userId);
      setUsers(users.map((user) => (user._id === userId ? { ...user, blocked: true } : user)));
      setToast({ message: 'User blocked successfully', type: 'success' });
      setSelectedUser(null);
    } catch (error) {
      setToast({ message: 'Failed to block user', type: 'error' });
    }
  };

  const filteredUsers =
    filter === 'all'
      ? users
      : filter === 'students'
      ? users.filter((user) => user.role === 'student')
      : filter === 'recruiters'
      ? users.filter((user) => user.role === 'recruiter')
      : users.filter((user) => user.role === 'recruiter' && !user.approved);

  if (loading) {
    return <Loader fullScreen />;
  }

  return (
    <div className="flex min-h-screen bg-gray-50 dark:bg-gray-900">
      <Sidebar />
      <div className="flex-1 p-8">
        <div className="max-w-7xl mx-auto">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-200 mb-2">User Management</h1>
            <p className="text-gray-600 dark:text-gray-400">Manage students, recruiters, and approvals</p>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 mb-8">
            <div className="flex gap-4">
              <button
                onClick={() => setFilter('all')}
                className={`px-6 py-2 rounded-lg font-semibold transition-colors ${
                  filter === 'all'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                }`}
              >
                All Users ({users.length})
              </button>
              <button
                onClick={() => setFilter('students')}
                className={`px-6 py-2 rounded-lg font-semibold transition-colors ${
                  filter === 'students'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                }`}
              >
                Students ({users.filter((u) => u.role === 'student').length})
              </button>
              <button
                onClick={() => setFilter('recruiters')}
                className={`px-6 py-2 rounded-lg font-semibold transition-colors ${
                  filter === 'recruiters'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                }`}
              >
                Recruiters ({users.filter((u) => u.role === 'recruiter').length})
              </button>
              <button
                onClick={() => setFilter('pending')}
                className={`px-6 py-2 rounded-lg font-semibold transition-colors ${
                  filter === 'pending'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                }`}
              >
                Pending Approval (
                {users.filter((u) => u.role === 'recruiter' && !u.approved).length})
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {filteredUsers.map((user) => (
              <div
                key={user._id}
                className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow"
              >
                <div className="flex justify-between items-start mb-4">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-full overflow-hidden bg-blue-100 flex items-center justify-center">
                      {user.role === 'student' && user.avatarUrl ? (
                        <img src={user.avatarUrl} alt={user.name} className="w-full h-full object-cover" />
                      ) : user.role === 'recruiter' && user.logoUrl ? (
                        <img src={user.logoUrl} alt={user.name} className="w-full h-full object-cover" />
                      ) : user.role === 'student' ? (
                        <User className="w-6 h-6 text-blue-600" />
                      ) : (
                        <Building className="w-6 h-6 text-blue-600" />
                      )}
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-gray-800 dark:text-gray-200">{user.name}</h3>
                      <p className="text-sm text-gray-600 dark:text-gray-400 capitalize">{user.role}</p>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    {user.role === 'recruiter' && (
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-semibold ${
                          user.approved
                            ? 'bg-green-100 text-green-700'
                            : 'bg-yellow-100 text-yellow-700'
                        }`}
                      >
                        {user.approved ? 'Approved' : 'Pending'}
                      </span>
                    )}
                    {user.blocked && (
                      <span className="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        Blocked
                      </span>
                    )}
                  </div>
                </div>

                <div className="space-y-2 mb-4 text-sm">
                  <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                    <Mail className="w-4 h-4" />
                    {user.email}
                  </div>
                  {user.role === 'student' && (
                    <div className="text-gray-600 dark:text-gray-400">
                      <p className="font-medium">{user.university}</p>
                      <p>{user.degree}</p>
                    </div>
                  )}
                  {user.role === 'recruiter' && (
                    <div className="text-gray-600 dark:text-gray-400">
                      <p className="font-medium">{user.company}</p>
                    </div>
                  )}
                </div>

                <button
                  onClick={() => setSelectedUser(user)}
                  className="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                >
                  View Details
                </button>
              </div>
            ))}
          </div>

          {filteredUsers.length === 0 && (
            <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-12 text-center">
              <User className="w-16 h-16 text-gray-400 mx-auto mb-4" />
              <h3 className="text-xl font-bold text-gray-800 dark:text-gray-200 mb-2">No users found</h3>
              <p className="text-gray-600 dark:text-gray-400">No users match the selected filter</p>
            </div>
          )}
        </div>
      </div>

      <Modal
        isOpen={!!selectedUser}
        onClose={() => setSelectedUser(null)}
        title="User Details"
        size="lg"
      >
        {selectedUser && (
          <div className="space-y-6">
            <div className="flex items-start gap-4">
              <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                {selectedUser.role === 'student' ? (
                  <User className="w-8 h-8 text-blue-600" />
                ) : (
                  <Building className="w-8 h-8 text-blue-600" />
                )}
              </div>
              <div className="flex-1">
                <h3 className="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-1">{selectedUser.name}</h3>
                <p className="text-gray-600 dark:text-gray-400 capitalize mb-2">{selectedUser.role}</p>
                <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400 text-sm">
                  <Mail className="w-4 h-4" />
                  {selectedUser.email}
                </div>
              </div>
              {selectedUser.role === 'recruiter' && (
                <span
                  className={`px-3 py-1 rounded-full text-xs font-semibold ${
                    selectedUser.approved
                      ? 'bg-green-100 text-green-700'
                      : 'bg-yellow-100 text-yellow-700'
                  }`}
                >
                  {selectedUser.approved ? 'Approved' : 'Pending'}
                </span>
              )}
            </div>

            {selectedUser.role === 'student' && (
              <div className="border-t pt-4 space-y-4">
                <h4 className="font-semibold text-gray-800 dark:text-gray-200 mb-2">Student Profile</h4>
                <div className="flex items-center gap-4">
                  {selectedUser.avatarUrl && (
                    <img src={selectedUser.avatarUrl} alt="avatar" className="w-16 h-16 rounded-full object-cover" />
                  )}
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">University:</span>{' '}
                      <span className="font-medium text-gray-800 dark:text-gray-200">{selectedUser.university}</span>
                    </div>
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Degree:</span>{' '}
                      <span className="font-medium text-gray-800 dark:text-gray-200">{selectedUser.degree}</span>
                    </div>
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Graduation Year:</span>{' '}
                      <span className="font-medium text-gray-800 dark:text-gray-200">{selectedUser.graduationYear}</span>
                    </div>
                    {selectedUser.resumeUrl && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">Resume:</span>{' '}
                        <a href={selectedUser.resumeUrl} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">View</a>
                      </div>
                    )}
                    {selectedUser.githubUrl && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">GitHub:</span>{' '}
                        <a href={selectedUser.githubUrl} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">{selectedUser.githubUrl}</a>
                      </div>
                    )}
                    {selectedUser.linkedinUrl && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">LinkedIn:</span>{' '}
                        <a href={selectedUser.linkedinUrl} target="_blank" rel="noreferrer" className="text-blue-600 hover:underline">{selectedUser.linkedinUrl}</a>
                      </div>
                    )}
                  </div>
                </div>
                {selectedUser.skills && selectedUser.skills.length > 0 && (
                  <div>
                    <span className="text-gray-600 dark:text-gray-400 text-sm">Skills:</span>
                    <div className="flex flex-wrap gap-2 mt-2">
                      {selectedUser.skills.map((skill, index) => (
                        <span key={index} className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">
                          {skill}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
            )}

            {selectedUser.role === 'recruiter' && (
              <div className="border-t pt-4 space-y-4">
                <h4 className="font-semibold text-gray-800 dark:text-gray-200 mb-2">Company Information</h4>
                <div className="flex items-start gap-4">
                  {selectedUser.logoUrl && (
                    <img src={selectedUser.logoUrl} alt="logo" className="w-16 h-16 rounded object-cover" />
                  )}
                  <div className="space-y-2 text-sm">
                    <div>
                      <span className="text-gray-600 dark:text-gray-400">Company:</span>{' '}
                      <span className="font-medium text-gray-800 dark:text-gray-200">{selectedUser.company}</span>
                    </div>
                    {selectedUser.companyDescription && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">Description:</span>
                        <p className="text-gray-800 dark:text-gray-200 mt-1">{selectedUser.companyDescription}</p>
                      </div>
                    )}
                    {selectedUser.website && (
                      <div>
                        <span className="text-gray-600 dark:text-gray-400">Website:</span>{' '}
                        <a
                          href={selectedUser.website}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-blue-600 hover:underline dark:text-blue-400"
                        >
                          {selectedUser.website}
                        </a>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            )}

            <div className="flex justify-end gap-4 border-t pt-4">
              {selectedUser.role === 'recruiter' && !selectedUser.approved && !selectedUser.blocked && (
                <button
                  onClick={() => handleApprove(selectedUser._id)}
                  className="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors"
                >
                  <CheckCircle className="w-5 h-5" />
                  Approve Recruiter
                </button>
              )}
              {!selectedUser.blocked && (
                <button
                  onClick={() => handleBlock(selectedUser._id)}
                  className="flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition-colors"
                >
                  <XCircle className="w-5 h-5" />
                  Block User
                </button>
              )}
            </div>
          </div>
        )}
      </Modal>

      {toast && <Toast {...toast} onClose={() => setToast(null)} />}
    </div>
  );
};

export default Users;