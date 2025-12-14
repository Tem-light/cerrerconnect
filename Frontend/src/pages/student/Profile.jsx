import { useState, useRef } from 'react';
import { useAuth } from '../../context/AuthContext';
import { userAPI } from '../../utils/api';
import Sidebar from '../../components/Sidebar';
import Toast from '../../components/Toast';
import { User, Mail, Phone, GraduationCap, Calendar, FileText, Plus, X, Upload, Link as LinkIcon, Image as ImageIcon } from 'lucide-react';

const Profile = () => {
  const { user, updateUser } = useAuth();
  const profile = user?.profile || {};
  const [formData, setFormData] = useState({
    name: user?.name || '',
    email: user?.email || '',
    university: profile.university || '',
    degree: profile.degree || '',
    graduationYear: profile.graduationYear || 2025,
    phone: profile.phone || '',
    githubUrl: profile.githubUrl || '',
    linkedinUrl: profile.linkedinUrl || '',
    avatarUrl: profile.avatarUrl || '',
  });
  const [skills, setSkills] = useState(profile.skills || []);
  const [newSkill, setNewSkill] = useState('');
  const [resume, setResume] = useState(profile.resumeUrl || '');
  const [loading, setLoading] = useState(false);
  const [toast, setToast] = useState(null);
  const fileInputRef = useRef(null);
  const avatarInputRef = useRef(null);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const [updatedUser, updatedProfile] = await Promise.all([
        userAPI.updateProfile(user._id, { name: formData.name, email: formData.email }),
        userAPI.updateStudentProfile(user._id, {
          university: formData.university,
          degree: formData.degree,
          graduationYear: Number(formData.graduationYear),
          skills,
          resumeUrl: resume,
          phone: formData.phone,
          githubUrl: formData.githubUrl,
          linkedinUrl: formData.linkedinUrl,
          avatarUrl: formData.avatarUrl,
        }),
      ]);

      updateUser({ name: updatedUser.name, email: updatedUser.email, profile: updatedProfile });
      setToast({ message: 'Profile updated successfully!', type: 'success' });
    } catch (error) {
      console.error('Error updating profile:', error);
      setToast({ message: error?.message || 'Failed to update profile', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const addSkill = () => {
    const trimmedSkill = newSkill.trim();
    if (trimmedSkill && !skills.includes(trimmedSkill)) {
      setSkills([...skills, trimmedSkill]);
      setNewSkill('');
    }
  };

  const removeSkill = (skillToRemove) => {
    setSkills(skills.filter((skill) => skill !== skillToRemove));
  };

  const handleResumeUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    try {
      setLoading(true);
      const res = await userAPI.uploadResume(user._id, file);
      const newUrl = res.resumeUrl || res?.profile?.resumeUrl || '';
      setResume(newUrl);
      updateUser({ profile: res.profile });
      setToast({ message: 'Resume uploaded!', type: 'success' });
    } catch (error) {
      console.error('Resume upload failed:', error);
      setToast({ message: 'Failed to upload resume', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  const handleAvatarUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    try {
      setLoading(true);
      const res = await userAPI.uploadAvatar(user._id, file);
      const rawUrl = res.avatarUrl || res?.profile?.avatarUrl || '';
      const cacheBusted = rawUrl ? `${rawUrl}${rawUrl.includes('?') ? '&' : '?'}t=${Date.now()}` : '';
      setFormData((prev) => ({ ...prev, avatarUrl: cacheBusted }));
      // keep token and other fields, but refresh profile with latest server data while using cache-busted avatar locally
      const newProfile = { ...(res.profile || {}), avatarUrl: cacheBusted };
      updateUser({ profile: newProfile });
      setToast({ message: 'Profile photo updated!', type: 'success' });
    } catch (error) {
      console.error('Avatar upload failed:', error);
      setToast({ message: 'Failed to upload photo', type: 'error' });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen bg-gray-50 dark:bg-gray-900">
      <Sidebar />
      <div className="flex-1 p-8">
        <div className="max-w-4xl mx-auto">
          <div className="mb-8">
            <h1 className="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">My Profile</h1>
            <p className="text-gray-600 dark:text-gray-300">Manage your personal information and resume</p>
          </div>

          <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md p-8">
            {/* Avatar Section */}
            <div className="flex items-center gap-6 mb-8">
              <div className="w-20 h-20 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center bg-">
                {formData.avatarUrl ? (
                  <img src={formData.avatarUrl} alt="Avatar" className="w-full h-full object-cover item-center justify-center" />
                ) : (
                  <ImageIcon className="w-8 h-8 text-gray-400" />
                )}
              </div>
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-300 mb-2">Profile Photo</p>
                <input
                  type="file"
                  accept="image/*"
                  ref={avatarInputRef}
                  className="hidden"
                  onChange={handleAvatarUpload}
                />
                <button
                  type="button"
                  onClick={() => avatarInputRef.current?.click()}
                  className="px-4 py-2 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  <Upload className="inline w-4 h-4 mr-2" /> Upload Photo
                </button>
              </div>
            </div>

            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {[
                  { label: 'Full Name', name: 'name', type: 'text', icon: <User className="w-4 h-4 inline mr-2" /> },
                  { label: 'Email Address', name: 'email', type: 'email', icon: <Mail className="w-4 h-4 inline mr-2" /> },
                  { label: 'Phone Number', name: 'phone', type: 'tel', icon: <Phone className="w-4 h-4 inline mr-2" /> },
                  { label: 'University', name: 'university', type: 'text', icon: <GraduationCap className="w-4 h-4 inline mr-2" /> },
                  { label: 'Degree', name: 'degree', type: 'text', icon: <FileText className="w-4 h-4 inline mr-2" /> },
                  { label: 'Graduation Year', name: 'graduationYear', type: 'number', icon: <Calendar className="w-4 h-4 inline mr-2" />, min: 2024, max: 2035 },
                ].map(({ label, name, type, icon, min, max }) => (
                  <div key={name}>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      {icon}
                      {label}
                    </label>
                    <input
                      type={type}
                      name={name}
                      value={formData[name]}
                      onChange={handleChange}
                      required={['name','email','university','degree','graduationYear'].includes(name)}
                      min={min}
                      max={max}
                      className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                    />
                  </div>
                ))}
              </div>

              {/* Social Links */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <LinkIcon className="w-4 h-4 inline mr-2" /> GitHub URL
                  </label>
                  <input
                    type="url"
                    name="githubUrl"
                    placeholder="https://github.com/username"
                    value={formData.githubUrl}
                    onChange={handleChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <LinkIcon className="w-4 h-4 inline mr-2" /> LinkedIn URL
                  </label>
                  <input
                    type="url"
                    name="linkedinUrl"
                    placeholder="https://www.linkedin.com/in/username"
                    value={formData.linkedinUrl}
                    onChange={handleChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  />
                </div>
              </div>

              {/* Skills Section */}
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Skills</label>
                <div className="flex gap-2 mb-3">
                  <input
                    type="text"
                    value={newSkill}
                    onChange={(e) => setNewSkill(e.target.value)}
                    onKeyPress={(e) => e.key === 'Enter' && (e.preventDefault(), addSkill())}
                    placeholder="Add a skill"
                    className="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300"
                  />
                  <button
                    type="button"
                    onClick={addSkill}
                    className="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors"
                  >
                    <Plus className="w-5 h-5" />
                  </button>
                </div>
                <div className="flex flex-wrap gap-2">
                  {skills.map((skill, index) => (
                    <span
                      key={index}
                      className="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium flex items-center gap-2"
                    >
                      {skill}
                      <button type="button" onClick={() => removeSkill(skill)} className="hover:text-blue-900">
                        <X className="w-4 h-4" />
                      </button>
                    </span>
                  ))}
                </div>
              </div>

              {/* Resume Upload Section */}
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Resume</label>
                <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center dark:border-gray-600">
                  <FileText className="w-12 h-12 text-gray-400 mx-auto mb-3" />
                  <p className="text-gray-600 dark:text-gray-300 mb-2">
                    {resume ? `Uploaded: ${resume}` : 'No resume uploaded yet'}
                  </p>
                  <input
                    type="file"
                    ref={fileInputRef}
                    className="hidden"
                    onChange={handleResumeUpload}
                    accept=".pdf,.doc,.docx"
                  />
                  <button
                    type="button"
                    onClick={() => fileInputRef.current.click()}
                    className="text-blue-600 font-semibold hover:text-blue-700 flex items-center justify-center mx-auto gap-2"
                  >
                    <Upload className="w-4 h-4" /> Upload Resume
                  </button>
                </div>
              </div>

              {/* Buttons */}
              <div className="flex justify-end gap-4 pt-6 border-t">
                <button
                  type="button"
                  className="px-6 py-3 border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={loading}
                  className="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-blue-400"
                >
                  {loading ? 'Saving...' : 'Save Changes'}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      {toast && <Toast {...toast} onClose={() => setToast(null)} />}
    </div>
  );
};

export default Profile;
