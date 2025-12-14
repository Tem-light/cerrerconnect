import { Target, Users, Zap, Shield } from 'lucide-react';
import { Link } from 'react-router-dom';

const About = () => {
  const values = [
    {
      icon: Target,
      title: 'Mission-Driven',
      description: 'Empowering students to achieve their career goals through meaningful connections',
    },
    {
      icon: Users,
      title: 'Community-Focused',
      description: 'Building a supportive network of students, professionals, and recruiters',
    },
    {
      icon: Zap,
      title: 'Innovation',
      description: 'Leveraging technology to create seamless job search and hiring experiences',
    },
    {
      icon: Shield,
      title: 'Trust & Security',
      description: 'Verified companies and protected user data for a safe platform',
    },
  ];

  return (
    <div className="min-h-screen bg-white">
      <section className="bg-gradient-to-br from-blue-600 to-blue-700 text-white py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="max-w-3xl">
            <h1 className="text-5xl md:text-6xl font-bold mb-6">About CareerConnect</h1>
            <p className="text-xl md:text-2xl text-blue-100">
              We're on a mission to bridge the gap between talented students and amazing career
              opportunities
            </p>
          </div>
        </div>
      </section>

      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-4xl font-bold text-gray-800 mb-6">Our Story</h2>
              <div className="space-y-4 text-lg text-gray-700">
                <p>
                  Founded in 2024, CareerConnect was born from a simple observation: students and
                  recent graduates struggle to find relevant internships and entry-level positions,
                  while companies struggle to connect with qualified young talent.
                </p>
                <p>
                  We built CareerConnect to solve this problem by creating a platform that makes it
                  easy for students to discover opportunities and for recruiters to find their next
                  great hire.
                </p>
                <p>
                  Today, we're proud to serve thousands of students and hundreds of companies,
                  facilitating meaningful connections that launch careers and build teams.
                </p>
              </div>
            </div>
            <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-12">
              <div className="space-y-8">
                <div>
                  <div className="text-5xl font-bold text-blue-600 mb-2">10,000+</div>
                  <p className="text-gray-700 font-semibold">Active Students</p>
                </div>
                <div>
                  <div className="text-5xl font-bold text-blue-600 mb-2">500+</div>
                  <p className="text-gray-700 font-semibold">Partner Companies</p>
                </div>
                <div>
                  <div className="text-5xl font-bold text-blue-600 mb-2">5,000+</div>
                  <p className="text-gray-700 font-semibold">Successful Placements</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="py-20 bg-gray-50">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-800 mb-4">Our Values</h2>
            <p className="text-xl text-gray-600">
              The principles that guide everything we do
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {values.map((value, index) => (
              <div
                key={index}
                className="bg-white rounded-xl shadow-md p-8 text-center hover:shadow-xl transition-shadow"
              >
                <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                  <value.icon className="w-8 h-8 text-blue-600" />
                </div>
                <h3 className="text-xl font-bold text-gray-800 mb-3">{value.title}</h3>
                <p className="text-gray-600">{value.description}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className="py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-4xl font-bold text-gray-800 mb-4">How It Works</h2>
            <p className="text-xl text-gray-600">
              Simple steps to connect students with opportunities
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div className="text-center">
              <div className="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold">
                1
              </div>
              <h3 className="text-2xl font-bold text-gray-800 mb-4">Create Your Profile</h3>
              <p className="text-gray-600">
                Sign up and build your profile with your education, skills, and career
                preferences
              </p>
            </div>

            <div className="text-center">
              <div className="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold">
                2
              </div>
              <h3 className="text-2xl font-bold text-gray-800 mb-4">Discover Opportunities</h3>
              <p className="text-gray-600">
                Browse jobs and internships that match your profile and apply with one click
              </p>
            </div>

            <div className="text-center">
              <div className="w-20 h-20 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-6 text-white text-3xl font-bold">
                3
              </div>
              <h3 className="text-2xl font-bold text-gray-800 mb-4">Connect & Get Hired</h3>
              <p className="text-gray-600">
                Receive responses from recruiters and land your dream internship or job
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="py-20 bg-gradient-to-r from-blue-600 to-blue-700 text-white">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-4xl font-bold mb-6">Join Our Growing Community</h2>
          <p className="text-xl mb-8 text-blue-100">
            Whether you're a student looking for opportunities or a company seeking talent, we're
            here to help
          </p>
          <div className="flex gap-4 justify-center">
            <Link
              to="/register"
              className="bg-white text-blue-600 px-8 py-4 rounded-lg font-bold text-lg hover:bg-gray-100 transition-colors"
            >
              Get Started
            </Link>
            <Link
              to="/contact"
              className="bg-blue-500 text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-blue-400 transition-colors"
            >
              Contact Us
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
};

export default About;
