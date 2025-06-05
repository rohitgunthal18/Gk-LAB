/**
 * GK Lab Admin - Immediate Style Initialization
 * This file applies critical admin styling as soon as possible
 */

// Apply admin wrapper class to body
document.body.classList.add('admin-body');

// Create style tag for immediate styling
const styleTag = document.createElement('style');
styleTag.textContent = `
  body.admin-body {
    background-color: #F8F9FA;
    margin: 0;
    padding: 0;
  }
  
  .admin-wrapper {
    display: flex;
    min-height: 100vh;
    background-color: #F8F9FA;
  }
  
  .admin-sidebar {
    width: 250px;
    background-color: #2c3e50;
    color: #FFFFFF;
    position: fixed;
    left: 0;
    top: 0;
    height: 100%;
    overflow-y: auto;
    z-index: 100;
    transition: all 0.3s ease;
  }
  
  .admin-main {
    flex: 1;
    margin-left: 250px;
    transition: all 0.3s ease;
  }
`;

document.head.appendChild(styleTag); 