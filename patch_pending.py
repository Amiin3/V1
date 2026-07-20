import sys, re

file_path = sys.argv[1]
try:
    with open(file_path, 'r') as f:
        content = f.read()
except FileNotFoundError:
    print(f"[ERROR] File {file_path} tidak ditemukan!")
    sys.exit(1)

# Regex aman untuk mencari blok fungsi get_pending_transaction hingga definisi fungsi berikutnya
pattern = re.compile(r"(^[ \t]*)def get_pending_transaction\(self\):[\s\S]*?(?=\n[ \t]*def |\Z)", re.MULTILINE)

def repl(match):
    indent = match.group(1)
    return f"""{indent}def get_pending_transaction(self):
{indent}    '''
{indent}    Mengambil daftar transaksi yang masih berstatus pending.
{indent}    '''
{indent}    endpoint = f"{{self.base_url}}/transaction/history"
{indent}    try:
{indent}        response = self.session.get(endpoint, params={{"status": "pending"}}, headers=self.headers)
{indent}        if response.status_code == 200:
{indent}            return response.json()
{indent}        print(f"[ERROR] get_pending_transaction status: {{response.status_code}}")
{indent}        return {{"status": False, "message": f"HTTP Error {{response.status_code}}", "data": None}}
{indent}    except Exception as e:
{indent}        print(f"[EXCEPTION] get_pending_transaction error: {{e}}")
{indent}        return {{"status": False, "message": str(e), "data": None}}"""

if not pattern.search(content):
    print(f"[FAILED] Fungsi get_pending_transaction tidak ditemukan di {file_path}! Pastikan nama file benar.")
    sys.exit(1)

new_content = pattern.sub(repl, content, count=1)

with open(file_path, 'w') as f:
    f.write(new_content)
    
print(f"[SUCCESS] Fungsi get_pending_transaction di {file_path} berhasil diperbarui!")
