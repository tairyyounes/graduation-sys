import os
import time
import pytest
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

BASE_URL = os.getenv("APP_URL", "http://localhost:8000")

def perform_login(driver, email, password):
    """Helper to perform web login through the UI form."""
    driver.get(f"{BASE_URL}/login")
    
    # Wait for login inputs
    email_input = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "email"))
    )
    password_input = driver.find_element(By.ID, "password")
    
    email_input.clear()
    email_input.send_keys(email)
    password_input.clear()
    password_input.send_keys(password)
    
    submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    submit_btn.click()


class TestAdminRole:
    """Selenium E2E Tests for Admin Role."""

    def test_admin_login_and_dashboard_navigation(self, driver):
        # 1. Login as Admin
        perform_login(driver, "testadmin@example.com", "password123")

        # 2. Wait for redirect to admin dashboard
        WebDriverWait(driver, 15).until(
            EC.url_contains("/admin/dashboard")
        )
        assert "/admin/dashboard" in driver.current_url, f"Expected /admin/dashboard, got {driver.current_url}"

        # 3. Verify Admin Vue app root is mounted
        admin_app = WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.ID, "admin-dashboard"))
        )
        assert admin_app is not None

        # 4. Wait for page title / content to render
        time.sleep(2)  # Give Vue dynamic components time to hydrate
        page_source = driver.page_source
        
        # Verify authenticated user state is populated
        assert "testadmin@example.com" in page_source or "admin" in page_source.lower()
        print("\n✔ [Selenium] Admin login & dashboard verification passed!")


class TestDepartmentHeadRole:
    """Selenium E2E Tests for Department Head Role."""

    def test_dept_head_login_and_dashboard_access(self, driver):
        # 1. Login as Department Head
        perform_login(driver, "testhead@example.com", "password123")

        # 2. Wait for redirect to department dashboard
        WebDriverWait(driver, 15).until(
            EC.url_contains("/department/dashboard")
        )
        assert "/department/dashboard" in driver.current_url, f"Expected /department/dashboard, got {driver.current_url}"

        # 3. Verify Department Vue app root is mounted
        dept_app = WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.ID, "department-dashboard"))
        )
        assert dept_app is not None

        time.sleep(2)
        page_source = driver.page_source
        assert "testhead@example.com" in page_source or "department_head" in page_source
        print("\n✔ [Selenium] Department Head login & dashboard verification passed!")


class TestDepartmentMemberRole:
    """Selenium E2E Tests for Department Member Role."""

    def test_dept_member_login_and_dashboard_access(self, driver):
        # 1. Login as Department Member
        perform_login(driver, "testmember@example.com", "password123")

        # 2. Wait for redirect to department dashboard
        WebDriverWait(driver, 15).until(
            EC.url_contains("/department/dashboard")
        )
        assert "/department/dashboard" in driver.current_url, f"Expected /department/dashboard, got {driver.current_url}"

        # 3. Verify Department Vue app root is mounted
        dept_app = WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.ID, "department-dashboard"))
        )
        assert dept_app is not None

        time.sleep(2)
        page_source = driver.page_source
        assert "testmember@example.com" in page_source or "department_member" in page_source
        print("\n✔ [Selenium] Department Member login & dashboard verification passed!")
