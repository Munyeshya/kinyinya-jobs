# Kinyinya Jobs User Guide

This guide explains how to use the Kinyinya Jobs platform according to each account type: Job Seeker, Employer, and Administrator.

## Opening the system

Use the address provided by the person who installed the system. Common local addresses are:

- PHP development server: `http://127.0.0.1:8000`
- XAMPP: `http://localhost/kinyinya-jobs`

The navigation menu changes automatically after login and only displays pages available to the signed-in role.

## Features by user role

| Feature | Job Seeker | Employer | Administrator |
|---|:---:|:---:|:---:|
| Create an account | Yes | Yes | No |
| Complete a profile | Yes | Yes | No |
| Search approved jobs | Yes | No | No |
| Apply for a job | Yes | No | No |
| Post and manage vacancies | No | Yes | No |
| Review applicants and CVs | No | Yes | No |
| Update application status | No | Yes | No |
| Approve or reject vacancies | No | No | Yes |
| Activate or deactivate accounts | No | No | Yes |
| View platform statistics | No | No | Yes |
| Use application messages | Yes | Yes | No |

## Creating an account and signing in

Job seekers and employers use the same account form.

1. Open the homepage and select **Create account**.
2. Select either **Job seeker** or **Employer** as the account type.
3. Enter the full name or company name, email address, and password.
4. The password must contain at least six characters.
5. Select **Create account**.
6. Return to the login area and sign in with the registered email and password.

Additional information is completed after login. Administrator accounts cannot be created through the public registration page.

## Job Seeker Guide

### 1. Complete the profile

1. Sign in with a Job Seeker account.
2. Select **My profile** from the navigation menu.
3. Complete the name, skills, education, and location fields.
4. Optionally upload a CV in PDF, DOC, or DOCX format. The maximum size is 5 MB.
5. Select **Save profile**.

An uploaded CV can be opened through **View uploaded CV**. Uploading a new CV replaces the current file, and **Remove current CV** deletes it from the profile. Only the owner and an authorized employer reviewing an application can access it.

### 2. Find a vacancy

1. Select **Browse jobs**.
2. Use any combination of the available filters:
   - Keyword
   - Category
   - Job type
   - Location
3. Select **Search**.
4. Use **Reset** to remove all filters.
5. Select **View and apply** on a vacancy to read its full description, requirements, salary range, positions left, location, and expiration date.

Only administrator-approved vacancies that are open and whose expiration date has not been reached are displayed.

### 3. Apply for a vacancy

1. Open the vacancy details page.
2. Enter an optional cover letter.
3. Select **Submit application**.

A seeker can apply to the same vacancy only once. Applications cannot be submitted to rejected, closed, or expired vacancies.

### 4. Track applications

Select **My applications** to view all submitted applications and their current status:

- **Submitted:** The application was received.
- **Under review:** The employer is reviewing it.
- **Shortlisted:** The applicant has moved to the next stage.
- **Hired:** The employer selected the applicant.
- **Rejected:** The application was not selected.

Status-change notifications appear on the dashboard.

### 5. Message an employer

The **Message** action becomes available when an application is **Shortlisted** or **Hired**. Open it to read the application conversation and send a message to the employer. Messages appear after the page is refreshed.

## Employer Guide

### 1. Complete the company profile

1. Sign in with an Employer account.
2. Select **Company profile**.
3. Complete the company name, industry, location, and description.
4. Select **Save company profile**.

### 2. Post a vacancy

1. Select **Post a job**.
2. Complete the vacancy information:
   - Job title
   - Job type
   - Category
   - Job location
   - Description
   - Requirements
   - Minimum and maximum salary
   - Number of people needed
   - Expiration date
3. Select **Submit for approval**.

The expiration date must be after today, and the maximum salary cannot be lower than the minimum salary. Every new vacancy remains **Pending** until an administrator approves or rejects it.

### 3. Manage vacancies

The Employer Dashboard lists all company vacancies and their visibility status.

- Select **Edit** to update vacancy details.
- An edited vacancy returns to **Pending** so an administrator can approve the updated information before it becomes public again.
- Select **Close** to stop receiving applications.
- Select **Reopen** to make a manually closed vacancy available again.
- A vacancy turns itself off when its expiration date is reached and cannot be reopened without changing that date.
- Select **Review applicants** to see applications for a vacancy.
- The dashboard shows the total positions and how many are still available.

A rejected, pending, expired, closed, or fully filled vacancy is not visible to job seekers.

### 4. Review applicants

1. Select **Review applicants** beside the relevant vacancy.
2. Search applicants by name or skill.
3. Filter applicants by education level when needed.
4. Read the applicant's profile information and cover letter.
5. Select **View CV** when a CV is available.
6. Choose the appropriate application status and select **Update status**.

Updating a status creates an in-platform notification for the job seeker.
Marking an applicant **Hired** reduces the positions left. When no positions remain, the job is marked **Filled**, disappears from seeker searches, and no additional applicant can be marked hired. Increasing the number needed through **Edit** can create openings again, subject to administrator reapproval.

### 5. Message an applicant

The **Message applicant** action is available after the applicant is marked **Shortlisted** or **Hired**. Messages are stored inside the related application conversation.

### 6. Read notifications

The dashboard displays notifications for new applications and other recruitment activity. Opening the dashboard marks the displayed notifications as read.

## Administrator Guide

Administrator accounts are created during system setup or directly by an authorized database administrator. They cannot be registered from the public account form.

### 1. Review vacancies

1. Sign in with an Administrator account.
2. Open **Overview**.
3. Review the **Pending approvals** section.
4. Read the vacancy title, employer, category, type, number needed, positions left, location, salary, description, requirements, and expiration date.
5. Select **Approve** to make the vacancy visible to job seekers.
6. Select **Reject** to keep the vacancy hidden.

### 2. Manage user accounts

1. Find the **User accounts** table.
2. Select **Deactivate** to prevent a job seeker or employer from signing in.
3. Select **Activate** to restore login access.

Administrator accounts are protected and cannot be deactivated from the dashboard.

### 3. Monitor platform activity

The Administrator Overview includes:

- Number of registered job seekers
- Number of registered employers
- Number of visible job postings
- Total applications
- Applications grouped by status
- Postings grouped by category
- Recent applications
- Employer and account summaries

## Logging out and account safety

- Select **Log out** when finished, especially on a shared computer.
- Do not share account passwords.
- Upload only a CV intended for recruitment use.
- Contact the administrator if an account has been deactivated or if incorrect content is found in a vacancy.

## Common problems

### I cannot sign in

Check the email and password. The same message is shown for incorrect credentials and inactive accounts. Contact the administrator if the details are correct but login still fails.

### My vacancy is not visible

It may still be pending approval, may have been rejected, manually closed, may have reached its expiration date, or all available positions may already be filled. Check the status shown on the Employer Dashboard.

### I cannot apply again

Only one application per seeker is allowed for each vacancy.

### I cannot see the message button

Messaging is enabled only for applications marked **Shortlisted** or **Hired**.

### My CV upload failed

Confirm that the file is PDF, DOC, or DOCX and no larger than 5 MB.
