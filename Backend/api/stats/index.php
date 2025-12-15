<?php
// /api/stats/index.php

$user = requireAuthUser();
$scope = $segments[1] ?? '';

if ($scope === 'admin') {
    requireRole($user, ['admin']);
    $totalStudents = (int)($pdo->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch()['c'] ?? 0);
    $totalRecruiters = (int)($pdo->query("SELECT COUNT(*) c FROM users WHERE role='recruiter'")->fetch()['c'] ?? 0);
    $pendingRecruiters = (int)($pdo->query("SELECT COUNT(*) c FROM users WHERE role='recruiter' AND approved=0")->fetch()['c'] ?? 0);
    $totalJobs = (int)($pdo->query("SELECT COUNT(*) c FROM jobs")->fetch()['c'] ?? 0);
    $activeJobs = (int)($pdo->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch()['c'] ?? 0);
    $totalApplications = (int)($pdo->query("SELECT COUNT(*) c FROM applications")->fetch()['c'] ?? 0);

    jsonResponse([
        'totalStudents' => $totalStudents,
        'totalRecruiters' => $totalRecruiters,
        'pendingRecruiters' => $pendingRecruiters,
        'totalJobs' => $totalJobs,
        'activeJobs' => $activeJobs,
        'totalApplications' => $totalApplications,
    ]);
}

if ($scope === 'recruiter') {
    requireRole($user, ['recruiter']);

    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM jobs WHERE recruiter_id=?');
    $stmt->execute([$user['id']]);
    $totalJobs = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM jobs WHERE recruiter_id=? AND status='active'");
    $stmt->execute([$user['id']]);
    $activeJobs = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM applications a JOIN jobs j ON j.id=a.job_id WHERE j.recruiter_id=?');
    $stmt->execute([$user['id']]);
    $totalApplicants = (int)($stmt->fetch()['c'] ?? 0);

    jsonResponse([
        'totalJobs' => $totalJobs,
        'activeJobs' => $activeJobs,
        'totalApplicants' => $totalApplicants,
    ]);
}

if ($scope === 'student') {
    requireRole($user, ['student']);

    $stmt = $pdo->prepare('SELECT COUNT(*) c FROM applications WHERE student_id=?');
    $stmt->execute([$user['id']]);
    $appliedJobs = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM applications WHERE student_id=? AND status='pending'");
    $stmt->execute([$user['id']]);
    $pendingApplications = (int)($stmt->fetch()['c'] ?? 0);

    $stmt = $pdo->prepare("SELECT COUNT(*) c FROM applications WHERE student_id=? AND status='accepted'");
    $stmt->execute([$user['id']]);
    $acceptedApplications = (int)($stmt->fetch()['c'] ?? 0);
    
    // We added Total Jobs count in previous request
    $totalJobs = (int)($pdo->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch()['c'] ?? 0);

    jsonResponse([
        'totalJobs' => $totalJobs,
        'appliedJobs' => $appliedJobs,
        'pendingApplications' => $pendingApplications,
        'acceptedApplications' => $acceptedApplications,
    ]);
}

jsonResponse(['message' => 'Not Found'], 404);
