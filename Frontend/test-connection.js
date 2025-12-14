import axios from 'axios';

const testConnection = async () => {
  console.log('Testing backend connection...');

  try {
    const response = await axios.get('http://localhost:5000/');
    console.log('✓ Backend is running!');
    console.log('Response:', response.data);
    return true;
  } catch (error) {
    console.error('✗ Backend connection failed:', error.message);
    return false;
  }
};

testConnection();
