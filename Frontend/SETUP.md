# Career Connect - Full Stack Setup Guide

## Architecture Overview
This is a React + Vite frontend with a **core PHP + MySQL** backend:
- **Frontend**: React + Vite + TailwindCSS (port 5173)
- **Backend**: Core PHP (no framework) + MySQL + JWT Authentication (port 5000)
- **Proxy**: Vite dev server proxies `/api` and `/uploads` requests to the backend
## Prerequisites
- Node.js (v18 or higher) for the frontend
- PHP 8.0+ (for the backend dev server)
- MySQL 8+ (or MariaDB 10.6+)
- npm

## Setup Instructions

### 1. Install Dependencies

#### Frontend:
```bash
npm install
```

#### Backend:
No npm install is required for the PHP backend.

### 2. Configure Environment Variables

#### Backend (`server-php/.env`):
1. Copy `server-php/.env.example` to `server-php/.env`.
2. Set your MySQL connection values and `JWT_SECRET`.

### 3. Create the MySQL schema
Run `server-php/sql/schema.sql` against your MySQL instance (e.g. using MySQL Workbench, phpMyAdmin, or the `mysql` CLI).

### 4. Run the Application

#### Option 1: Run Both Servers Separately

**Terminal 1 - Backend (PHP):**
```bash
php -S localhost:5000 -t server-php/public server-php/router.php
```
Backend will run on http://localhost:5000

**Terminal 2 - Frontend:**
```bash
npm run dev
```
Frontend will run on http://localhost:5173

#### Option 2: Test Backend Only
```bash
php -S localhost:5000 -t server-php/public server-php/router.php
```
Then visit http://localhost:5000 in your browser. You should see:
```json
{"message": "Career Connect API is running"}
```

## Testing the Connection

1. Start the backend server
2. Start the frontend server
3. Open http://localhost:5173 in your browser
4. Try logging in or registering - API calls will be proxied to the backend

## API Endpoints

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `GET /api/auth/me` - Get current user

### Jobs
- `GET /api/jobs` - Get all jobs (with filters)
- `GET /api/jobs/:id` - Get single job
- `POST /api/jobs` - Create job (recruiter only)
- `PUT /api/jobs/:id` - Update job (recruiter only)
- `DELETE /api/jobs/:id` - Delete job (recruiter only)
- `GET /api/jobs/recruiter/my-jobs` - Get recruiter's jobs

### Applications
- `POST /api/applications/job/:jobId` - Apply for job (student only)
- `GET /api/applications/student/my-applications` - Get student's applications
- `GET /api/applications/job/:jobId/applicants` - Get job applicants (recruiter only)
- `PUT /api/applications/:id/status` - Update application status

### Users
- `GET /api/users` - Get all users (admin only)
- `PUT /api/users/:userId` - Update user name/email (self)
- `PUT /api/users/:userId/student-profile` - Update student profile (self)
- `POST /api/users/:userId/student-profile/avatar` - Upload student avatar
- `POST /api/users/:userId/student-profile/resume` - Upload student resume
- `PUT /api/users/:userId/recruiter-profile` - Update recruiter profile (self)
- `PUT /api/users/:recruiterId/approve` - Approve recruiter (admin only)
- `PUT /api/users/:userId/block` - Block user (admin only)

### Stats
- `GET /api/stats/admin` - Get admin dashboard stats

## Project Structure

```
career-connect/
├── server-php/             # Backend (Core PHP + MySQL)
│   ├── public/            # Document root (index.php, uploads)
│   ├── src/               # PHP source (router + handlers)
│   ├── sql/               # MySQL schema
│   ├── .env.example       # Example environment variables
│   └── router.php         # PHP built-in server router
│
├── src/                   # Frontend (React + Vite)
│   ├── components/        # Reusable components
│   ├── context/           # React context (Auth)
│   ├── pages/             # Page components
│   ├── routes/            # Route configuration
│   ├── utils/             # API utilities
│   └── main.jsx           # App entry point
│
├── vite.config.ts         # Vite configuration (with proxy)
└── package.json           # Frontend dependencies
```

## User Roles

The application supports three user roles:
1. **Student**: Can search jobs, apply, and manage applications
2. **Recruiter**: Can post jobs, manage jobs, and review applicants
3. **Admin**: Can manage all users and view system stats

## Troubleshooting

### Backend won't start:
- Check if port 5000 is available
- Verify `server-php/.env` exists and MySQL credentials are correct
- Ensure the MySQL schema has been applied (`server-php/sql/schema.sql`)

### Frontend won't connect to backend:
- Ensure backend is running on port 5000
- Check vite.config.ts has correct proxy configuration
- Clear browser cache and restart dev server

### Database connection errors:
- Ensure MySQL is running and reachable
- Check DB settings in `server-php/.env`
- Confirm the database and tables exist (run `server-php/sql/schema.sql`)

## Production Build

```bash
# Build frontend
npm run build

# The dist/ folder will contain production-ready files
# Serve these files with the backend or any static file server
```

## Notes

- The frontend now uses real API calls instead of mock data
- JWT tokens are stored in localStorage for authentication
- CORS is enabled on the backend for cross-origin requests
- The Vite proxy forwards all `/api/*` requests to `http://localhost:5000`
