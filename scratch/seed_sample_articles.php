<?php
/**
 * Seed sample articles for CareerNexaa portal
 */
require_once __DIR__ . '/../config/database.php';

$db = getDBConnection();

$sampleArticles = [
    [
        'title' => 'Top 10 High-Paying Remote Tech Careers in 2026',
        'slug' => 'top-10-high-paying-remote-tech-careers-in-2026',
        'excerpt' => 'Discover the most lucrative work-from-home technology positions offering global flexibility, competitive packages, and rapid career progression.',
        'content' => '<p>The shift towards asynchronous, distributed engineering teams has reached unprecedented maturity. In 2026, companies around the globe are competing fiercely for specialized tech talent who can deliver high impact without geographical limitations.</p>
<h2>1. Cloud & AI Systems Architects</h2>
<p>Organizations migrating workloads to hybrid cloud platforms need experienced architects who understand infrastructure automation, latency optimization, and distributed safety protocols.</p>
<ul>
    <li><strong>Average Compensation:</strong> $130,000 &ndash; $180,000 USD / year</li>
    <li><strong>Core Stack:</strong> Kubernetes, Terraform, AWS/GCP, Rust, Go</li>
</ul>
<h2>2. Full-Stack Full-Lifecycle Engineers</h2>
<p>Modern teams favor versatile problem solvers capable of driving modern web applications from frontend UX to resilient microservices and distributed databases.</p>
<h2>Key Skills to Land These Roles</h2>
<p>To stand out in high-paying remote roles, you need more than just code proficiency. Strong written communication, documented open-source contributions, and a verified portfolio are mandatory.</p>',
        'featured_image' => 'uploads/articles/career-advancement-ladder.jpg',
        'thumbnail_image' => 'uploads/articles/career-advancement-ladder.jpg',
        'category_id' => 3, // Remote & Work From Home
        'author_id' => 1,
        'seo_title' => 'Top 10 High-Paying Remote Tech Careers in 2026 | CareerNexaa',
        'meta_description' => 'Explore the highest paying remote tech jobs in 2026 with salary benchmarks, required tech stacks, and career strategies.',
        'keywords' => 'remote jobs, tech careers, work from home, software engineering, cloud architect',
        'tags' => 'Remote Work, Tech Jobs, Cloud, Architecture, High-Paying',
        'status' => 'published',
        'is_job_posting' => 0,
        'is_featured' => 1,
        'is_trending' => 1,
        'is_recommended' => 1,
        'views' => 45,
        'likes' => 8,
        'is_trash' => 0
    ],
    [
        'title' => 'How to Ace Technical Behavioral Interviews at Fortune 500 Companies',
        'slug' => 'how-to-ace-technical-behavioral-interviews-fortune-500',
        'excerpt' => 'A structured playbook for navigating STAR-method behavioral questions, culture assessments, and executive screening sessions.',
        'content' => '<p>While technical coding assessments filter baseline competencies, behavioral interviews determine who actually gets the offer letter. Hiring executives look for emotional intelligence, leadership ownership, and conflict resolution.</p>
<h2>The STAR Framework Perfected</h2>
<p>Always frame your answers using Situation, Task, Action, and Result:</p>
<ul>
    <li><strong>Situation:</strong> Contextualize the challenge in under 30 seconds.</li>
    <li><strong>Task:</strong> Explain your specific responsibility, not just the team&rsquo;s role.</li>
    <li><strong>Action:</strong> Detail the tactical steps you executed to overcome blockers.</li>
    <li><strong>Result:</strong> Quantify the outcome with business metrics (e.g., &ldquo;reduced latency by 40%&rdquo;).</li>
</ul>
<h2>Handling Difficult Scenarios</h2>
<p>When asked about past failures, demonstrate humility, self-awareness, and the exact preventive systems you instituted afterwards.</p>',
        'featured_image' => 'uploads/articles/interview-presentation-desk.jpg',
        'thumbnail_image' => 'uploads/articles/interview-presentation-desk.jpg',
        'category_id' => 5, // Resume & Interview Prep
        'author_id' => 1,
        'seo_title' => 'How to Ace Technical Behavioral Interviews | CareerNexaa',
        'meta_description' => 'Master the STAR framework and ace behavioral interviews at leading tech and multinational enterprises.',
        'keywords' => 'interview prep, behavioral interview, STAR method, career coaching, job offers',
        'tags' => 'Interview Prep, STAR Method, Fortune 500, Career Advice',
        'status' => 'published',
        'is_job_posting' => 0,
        'is_featured' => 0,
        'is_trending' => 1,
        'is_recommended' => 1,
        'views' => 28,
        'likes' => 5,
        'is_trash' => 0
    ],
    [
        'title' => 'Senior Frontend Developer (React & TypeScript) - FinTech Solutions',
        'slug' => 'senior-frontend-developer-react-typescript-fintech',
        'excerpt' => 'Join an international fintech firm building ultra-low-latency financial trading dashboards and mobile responsive portals.',
        'content' => '<p>We are seeking an experienced Senior Frontend Developer to lead client-facing dashboard engineering for next-generation algorithmic wealth management platforms.</p>
<h2>Key Responsibilities</h2>
<ul>
    <li>Architect performant, modular React components using TypeScript and TailwindCSS.</li>
    <li>Integrate real-time WebSocket data feeds for market pricing and portfolio analytics.</li>
    <li>Maintain 95%+ unit and end-to-end test coverage.</li>
</ul>
<h2>Requirements &amp; Qualifications</h2>
<p>5+ years of production experience in React, deep understanding of DOM performance optimization, and strong collaboration skills.</p>',
        'featured_image' => 'uploads/articles/business-partnership-deal.jpg',
        'thumbnail_image' => 'uploads/articles/business-partnership-deal.jpg',
        'category_id' => 2, // Private & Tech Jobs
        'author_id' => 1,
        'seo_title' => 'Senior Frontend Developer Opening | FinTech Solutions',
        'meta_description' => 'Hiring Senior Frontend Developer specializing in React and TypeScript for global fintech products.',
        'keywords' => 'frontend developer, react jobs, typescript, fintech careers, web engineering',
        'tags' => 'React, TypeScript, FinTech, Frontend, Remote Friendly',
        'status' => 'published',
        'is_job_posting' => 1,
        'job_company' => 'FinTech Global Systems',
        'job_location' => 'Remote / Hybrid',
        'job_type' => 'Full-time',
        'job_salary' => '$90,000 - $125,000 / yr',
        'job_deadline' => date('Y-m-d', strtotime('+30 days')),
        'is_featured' => 1,
        'is_trending' => 0,
        'is_recommended' => 1,
        'views' => 64,
        'likes' => 12,
        'is_trash' => 0
    ],
    [
        'title' => 'Algorithmic Trading & Risk Management Essentials for 2026',
        'slug' => 'algorithmic-trading-risk-management-essentials-2026',
        'excerpt' => 'Understand the mathematics, quantitative models, and capital preservation strategies behind automated market-making.',
        'content' => '<p>Modern quantitative trading relies on statistical models and automated execution to generate positive expected returns across diverse asset classes.</p>
<h2>Core Tenets of Capital Preservation</h2>
<p>Never risk more than 1-2% of total portfolio capital on an individual discretionary position. Position sizing must always be calibrated to market volatility (Average True Range).</p>
<h2>Backtesting without Overfitting</h2>
<p>Overfitting historical curves is the number one cause of algorithmic strategy failure. Use walk-forward optimization and out-of-sample data validation to stress-test your execution logic.</p>',
        'featured_image' => 'uploads/articles/crypto-trading-market.jpg',
        'thumbnail_image' => 'uploads/articles/crypto-trading-market.jpg',
        'category_id' => 7, // Trading
        'author_id' => 1,
        'seo_title' => 'Algorithmic Trading & Risk Management | CareerNexaa',
        'meta_description' => 'Guide to quantitative algorithmic trading, portfolio risk mitigation, and backtesting methodologies.',
        'keywords' => 'trading, algorithmic trading, finance, risk management, quantitative finance',
        'tags' => 'Trading, Algorithmic, Risk Management, Finance',
        'status' => 'published',
        'is_job_posting' => 0,
        'is_featured' => 0,
        'is_trending' => 1,
        'is_recommended' => 0,
        'views' => 37,
        'likes' => 6,
        'is_trash' => 0
    ],
    [
        'title' => 'Resume Writing in the Age of ATS: 7 Proven Strategies to Get Noticed',
        'slug' => 'resume-writing-ats-proven-strategies-to-get-noticed',
        'excerpt' => 'How Applicant Tracking Systems parse CVs in 2026 and how to structure your resume for maximum interview call-backs.',
        'content' => '<p>Over 90% of mid-to-large enterprises utilize automated screening software before a recruiter ever lays eyes on your resume. Understanding how ATS parsers interpret typography, tables, and skill keywords is essential.</p>
<h2>1. Keep Layouts Clean and Single-Column</h2>
<p>Complex multi-column resumes, floating text boxes, and graphic skill bars frequently cause parser errors, rendering your qualifications invisible.</p>
<h2>2. Tailor Keyword Phrases to the Job Circular</h2>
<p>Match the exact terminology found in the target job specifications. If the posting mentions &ldquo;Continuous Integration / CI-CD&rdquo;, include that exact phrasing rather than colloquial synonyms.</p>
<h2>3. Focus on Measurable Impact</h2>
<p>Replace generic job descriptions with accomplishment statements formatted as: <em>Action Verb + Context + Measurable Result</em>.</p>',
        'featured_image' => 'uploads/articles/corporate-boardroom-meeting.jpg',
        'thumbnail_image' => 'uploads/articles/corporate-boardroom-meeting.jpg',
        'category_id' => 4, // Career Advice & Tips
        'author_id' => 1,
        'seo_title' => 'Resume Writing Strategies for ATS Pass Rate | CareerNexaa',
        'meta_description' => 'Actionable resume optimization guide to beat automated ATS screeners and land high-caliber interviews.',
        'keywords' => 'resume tips, ATS resume, career advice, CV template, hiring process',
        'tags' => 'Resume, ATS, Career Tips, Job Search, Hiring',
        'status' => 'published',
        'is_job_posting' => 0,
        'is_featured' => 1,
        'is_trending' => 0,
        'is_recommended' => 1,
        'views' => 52,
        'likes' => 14,
        'is_trash' => 0
    ]
];

$stmt = $db->prepare("
    INSERT INTO articles (
        title, slug, excerpt, content, featured_image, thumbnail_image,
        category_id, author_id, seo_title, meta_description, keywords, tags,
        status, is_job_posting, job_company, job_location, job_type, job_salary, job_deadline,
        is_featured, is_trending, is_recommended, views, likes, is_trash, published_at, created_at, updated_at
    ) VALUES (
        :title, :slug, :excerpt, :content, :featured_image, :thumbnail_image,
        :category_id, :author_id, :seo_title, :meta_description, :keywords, :tags,
        :status, :is_job_posting, :job_company, :job_location, :job_type, :job_salary, :job_deadline,
        :is_featured, :is_trending, :is_recommended, :views, :likes, :is_trash, NOW(), NOW(), NOW()
    )
");

foreach ($sampleArticles as $art) {
    $stmt->execute([
        ':title' => $art['title'],
        ':slug' => $art['slug'],
        ':excerpt' => $art['excerpt'],
        ':content' => $art['content'],
        ':featured_image' => $art['featured_image'],
        ':thumbnail_image' => $art['thumbnail_image'],
        ':category_id' => $art['category_id'],
        ':author_id' => $art['author_id'],
        ':seo_title' => $art['seo_title'],
        ':meta_description' => $art['meta_description'],
        ':keywords' => $art['keywords'],
        ':tags' => $art['tags'],
        ':status' => $art['status'],
        ':is_job_posting' => $art['is_job_posting'],
        ':job_company' => $art['job_company'] ?? null,
        ':job_location' => $art['job_location'] ?? null,
        ':job_type' => $art['job_type'] ?? null,
        ':job_salary' => $art['job_salary'] ?? null,
        ':job_deadline' => $art['job_deadline'] ?? null,
        ':is_featured' => $art['is_featured'],
        ':is_trending' => $art['is_trending'],
        ':is_recommended' => $art['is_recommended'],
        ':views' => $art['views'],
        ':likes' => $art['likes'],
        ':is_trash' => $art['is_trash']
    ]);
    echo "Inserted: {$art['title']} (Slug: {$art['slug']})" . PHP_EOL;
}

echo "All sample articles seeded successfully!" . PHP_EOL;
