import React from 'react';
import AllApplicationsPage from './AllApplicationsPage';

/**
 * Wrapper component to safely link the App.jsx routing to the correctly implemented AllApplicationsPage
 * without needing to risk breaking existing route definitions.
 */
const AdminApplicationListPage = () => {
  return <AllApplicationsPage />;
};

export default AdminApplicationListPage;