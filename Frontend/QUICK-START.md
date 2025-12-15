# Quick Start Guide

## 🚀 Start the Application

### Method 1: Windows (PowerShell)
Run the provided PowerShell script:
```powershell
.\start-dev.ps1
```

### Method 2: Manual startup

**Step 1 - Backend (XAMPP):**
1. Open XAMPP Control Panel
2. Start **Apache** + **MySQL**
3. Confirm API health:
   - http://localhost/careerconnect/Backend/api

**Step 2 - Frontend (Vite on port 5173):**
```bash
npm run dev
```

## 📋 Prerequisites Checklist

- [x] Node.js installed
- [x] Frontend dependencies installed (`npm install`)
- [x] PHP 8+ installed
- [ ] MySQL running + schema applied (`Backend/sql/schema.sql`)

## 🔌 Connection Status

The frontend and backend are now connected:
- ✓ Vite proxy configured to forward `/api` to backend
- ✓ Axios interceptors set up for authentication
- ✓ All API endpoints mapped to backend routes
- ✓ CORS enabled on backend

## 🧪 Test the Connection

1. Start MySQL and apply the schema (`Backend/sql/schema.sql`).
2. Start backend: Apache serves it automatically from XAMPP
3. Start frontend: `npm run dev`
4. Visit: http://localhost:5173
5. Try registering or logging in

## 📍 Important URLs

- Frontend: http://localhost:5173
- Backend API: http://localhost/careerconnect/Backend/api
- Backend Health Check: http://localhost/careerconnect/Backend/api

## 🔑 Default Test Users

You'll need to register new users as the database is empty initially.

## ⚙️ What Changed

1. **vite.config.ts**: Added proxy to forward API calls to backend
2. **src/utils/api.js**: Uses real API calls (no mock data)
3. **Backend/.env**: Backend environment configuration
4. **PHP backend**: Implements `/api/*` routes consumed by the frontend

## 📚 Next Steps

- Register a new user
- Test login functionality
- Create jobs (as recruiter)
- Apply for jobs (as student)
- View admin dashboard (as admin)

For detailed documentation, see `SETUP.md`
