import requests
import pandas as pd
import urllib3
import warnings

# 忽略不安全的 SSL 警告
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def fetch_data_direct():
    # 這是您提到的 API 網址
    url = "https://lohas.taichung.gov.tw/webgis/api/query_land_data.ashx?type=inheritance&land=大甲區|義水段|09950000"
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    }
    
    print("正在嘗試以不驗證 SSL 的方式抓取資料...")
    try:
        # 加入 verify=False 繞過憑證驗證
        response = requests.get(url, headers=headers, timeout=15, verify=False)
        response.raise_for_status()
        
        # 嘗試解析 JSON
        data = response.json()
        
        # 將資料轉換為 DataFrame
        df = pd.DataFrame(data)
        
        df.to_csv("land_data_direct.csv", index=False, encoding="utf-8-sig")
        print(f"成功！已抓取 {len(df)} 筆資料並儲存至 land_data_direct.csv")
        print(df.head())

    except Exception as e:
        print(f"抓取失敗，錯誤訊息: {e}")

if __name__ == "__main__":
    fetch_data_direct()