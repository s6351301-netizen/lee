import sys
from playwright.sync_api import sync_playwright

def get_land_price(num1, num2):
    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            page = browser.new_page()
            page.goto("https://landquery.taichung.gov.tw/query/rwd/valueprice.jsp?menu=true&reqType=rwd")
            
            # 1. 選擇行政區
            page.select_option("select[name='SiteArea']", "BE-11")

            # 2. 【絕對穩定版】等待選單出現在 DOM 中
            # 這是最穩定的作法，確保元素出現後才進行後續操作
            page.wait_for_selector("select[name='R48']", timeout=60000)
            
            # 3. 確保選單內部的選項已經載入 (防止選單有空殼但沒內容)
            # 加入檢查確保不是 null 再讀取 options
            page.wait_for_function("""
                () => {
                    const el = document.querySelector('select[name="R48"]');
                    return el !== null && el.options.length > 1;
                }
            """, timeout=60000)
            
            # 4. 進行選擇
            page.select_option("select[name='R48']", "3652")
            
            # 5. 填值與查詢
            page.fill("input[name='NUM1']", num1)
            page.fill("input[name='NUM2']", num2)
            page.click("input[name='btnQuery']")
            
            # 6. 等待查詢結果的 table 出現
            page.wait_for_selector("table", timeout=15000)
            print(page.inner_text("table"))
            
            browser.close()
    except Exception as e:
        print(f"Error: {e}")

if __name__ == "__main__":
    num1 = sys.argv[1] if len(sys.argv) > 1 else "1040"
    num2 = sys.argv[2] if len(sys.argv) > 2 else "0000"
    get_land_price(num1, num2)