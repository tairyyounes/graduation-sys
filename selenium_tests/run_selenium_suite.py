import os
import time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options as ChromeOptions
from selenium.webdriver.edge.options import Options as EdgeOptions

BASE_URL = os.getenv("APP_URL", "http://localhost:8000")
SCREENSHOT_DIR = os.path.join(os.path.dirname(__file__), "screenshots")
os.makedirs(SCREENSHOT_DIR, exist_ok=True)

def create_driver():
    try:
        options = ChromeOptions()
        options.add_argument("--headless=new")
        options.add_argument("--disable-gpu")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--window-size=1920,1080")
        options.add_argument("--ignore-certificate-errors")
        return webdriver.Chrome(options=options)
    except Exception as e:
        print(f"Starting Edge driver instead: {e}")
        edge_options = EdgeOptions()
        edge_options.add_argument("--headless=new")
        edge_options.add_argument("--disable-gpu")
        edge_options.add_argument("--window-size=1920,1080")
        return webdriver.Edge(options=edge_options)

def test_role_flow(driver, role_name, email, password, expected_url_fragment, container_id):
    print(f"\n=======================================================")
    print(f"  ▶ TESTING ROLE: {role_name.upper()}")
    print(f"=======================================================")
    start_time = time.time()
    
    # 1. Open Login Page
    print(f"1. Navigating to {BASE_URL}/login ...")
    driver.get(f"{BASE_URL}/login")
    
    email_el = WebDriverWait(driver, 10).until(
        EC.presence_of_element_located((By.ID, "email"))
    )
    password_el = driver.find_element(By.ID, "password")
    
    # 2. Enter Credentials
    print(f"2. Entering credentials for {email} ...")
    email_el.clear()
    email_el.send_keys(email)
    password_el.clear()
    password_el.send_keys(password)
    
    # 3. Submit Form
    print(f"3. Submitting login form ...")
    submit_btn = driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
    submit_btn.click()
    
    # 4. Assert URL Redirect
    print(f"4. Waiting for redirect to '{expected_url_fragment}' ...")
    WebDriverWait(driver, 15).until(
        EC.url_contains(expected_url_fragment)
    )
    current_url = driver.current_url
    print(f"   ✔ Current URL: {current_url}")
    assert expected_url_fragment in current_url, f"Expected {expected_url_fragment} in {current_url}"
    
    # 5. Assert Vue App Mount
    print(f"5. Checking Vue application mount container (#{container_id}) ...")
    container_el = WebDriverWait(driver, 15).until(
        EC.presence_of_element_located((By.ID, container_id))
    )
    assert container_el is not None
    print(f"   ✔ Vue element #{container_id} mounted successfully!")
    
    # 6. Take Screenshot
    time.sleep(2)  # Wait for full dynamic render
    screenshot_path = os.path.join(SCREENSHOT_DIR, f"{role_name.lower().replace(' ', '_')}_dashboard.png")
    driver.save_screenshot(screenshot_path)
    print(f"6. Saved screenshot to: {screenshot_path}")
    
    # 7. Logout to clean session
    driver.delete_all_cookies()
    
    elapsed = time.time() - start_time
    print(f"✔ RESULT: {role_name} PASSED in {elapsed:.2f}s")
    return {
        "role": role_name,
        "status": "PASSED",
        "url": current_url,
        "time": f"{elapsed:.2f}s",
        "screenshot": screenshot_path
    }

def main():
    print("\n=======================================================")
    print("      SELENIUM WEBDRIVER E2E TEST SUITE RUNNER         ")
    print("=======================================================")
    print(f"Target: {BASE_URL}")
    print("Browser: Headless Chrome / Edge")
    
    driver = create_driver()
    results = []
    
    try:
        # Test 1: Admin
        results.append(test_role_flow(
            driver=driver,
            role_name="Admin",
            email="testadmin@example.com",
            password="password123",
            expected_url_fragment="/admin/dashboard",
            container_id="admin-dashboard"
        ))
        
        # Test 2: Department Head
        results.append(test_role_flow(
            driver=driver,
            role_name="Department Head",
            email="testhead@example.com",
            password="password123",
            expected_url_fragment="/department/dashboard",
            container_id="department-dashboard"
        ))
        
        # Test 3: Department Member
        results.append(test_role_flow(
            driver=driver,
            role_name="Department Member",
            email="testmember@example.com",
            password="password123",
            expected_url_fragment="/department/dashboard",
            container_id="department-dashboard"
        ))
        
    finally:
        driver.quit()
        
    print("\n=======================================================")
    print("               SELENIUM TEST SUMMARY                   ")
    print("=======================================================")
    for r in results:
        print(f"  • {r['role']:<20} [{r['status']}] in {r['time']} -> {r['url']}")
    print("=======================================================\n")

if __name__ == "__main__":
    main()
