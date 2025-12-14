import { MapPin, Briefcase, DollarSign, Calendar, Users } from 'lucide-react';

const JobCard = ({ job, onApply, showApplicants = false }) => {
  return (
    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-md hover:shadow-xl transition-shadow p-6 border border-gray-100 dark:border-gray-700">
      <div className="flex justify-between items-start mb-4">
        <div>
          <h3 className="text-xl font-bold text-gray-800 dark:text-gray-100 mb-2">{job.title}</h3>
          <p className="text-blue-600 font-semibold">{job.company}</p>
        </div>
        <span
          className={`px-3 py-1 rounded-full text-xs font-semibold ${
            job.type === 'Internship'
              ? 'bg-green-100 text-green-700'
              : 'bg-blue-100 text-blue-700'
          }`}
        >
          {job.type}
        </span>
      </div>

      <p className="text-gray-600 dark:text-gray-300 mb-4 line-clamp-2">{job.description}</p>

      <div className="space-y-2 mb-4">
        <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
          <MapPin className="w-4 h-4" />
          <span className="text-sm">{job.location}</span>
        </div>
        <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
          <Briefcase className="w-4 h-4" />
          <span className="text-sm">{job.category}</span>
        </div>
        <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
          <DollarSign className="w-4 h-4" />
          <span className="text-sm">
            {typeof job.salaryMin === 'number' && typeof job.salaryMax === 'number'
              ? `${job.salaryMin} - ${job.salaryMax}`
              : 'Not disclosed'}
          </span>
        </div>
        <div className="flex items-center gap-2 text-gray-600 dark:text-gray-400">
          <Calendar className="w-4 h-4" />
          <span className="text-sm">Posted on {new Date(job.createdAt).toLocaleDateString()}</span>
        </div>
        {showApplicants && (
          <div className="flex items-center gap-2 text-gray-600">
            <Users className="w-4 h-4" />
            <span className="text-sm">{job.applicantsCount || 0} / {job.openings || '-'} spots</span>
          </div>
        )}
      </div>

      <div className="flex flex-wrap gap-2 mb-4">
        {job.requirements.slice(0, 3).map((req, index) => (
          <span
            key={index}
            className="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded-full text-xs font-medium"
          >
            {req}
          </span>
        ))}
        {job.requirements.length > 3 && (
          <span className="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-100 rounded-full text-xs font-medium">
            +{job.requirements.length - 3} more
          </span>
        )}
      </div>

      {onApply && (
        <button
          onClick={() => onApply(job)}
          className="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
        >
          Apply Now
        </button>
      )}
    </div>
  );
};

export default JobCard;
