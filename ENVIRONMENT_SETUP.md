# Setting Up Laragon Environment for VS Code

It seems that your terminal in VS Code does not know where Laragon's PHP is located. To fix this, you have two options:

## Option 1: Use Laragon's Terminal (Easiest)
1. Open Laragon.
2. Click **Terminal** button in Laragon (it opens Cmder).
3. Navigate to your project:
   ```bash
   cd D:/Project/denji-skeleton
   ```
4. Run the server:
   ```bash
   php artisan serve
   ```

## Option 2: Add PHP to System PATH (Recommended)
This will allow you to run PHP commands directly in VS Code.

1. Open **Laragon**.
2. Go to **Menu > Tools > Path > Add Laragon to Path**.
   - Sometimes this doesn't work automatically. If not, proceed to step 3.
3. Find your PHP path:
   - Typically: `C:\laragon\bin\php\php-8.x.x` (where `x` is your version).
   - Copy this path.
4. Add to Windows Environment Variables:
   - Press `Win + R`, type `sysdm.cpl`, press Enter.
   - Go to **Advanced** tab > **Environment Variables**.
   - Under **System variables**, find **Path** and click **Edit**.
   - Click **New** and paste the PHP path you copied.
   - Click **OK** on all windows.
5. **Restart VS Code** (important!) for changes to take effect.
6. Verify by running `php -v` in VS Code terminal.

## Once PHP is working:
Run the backend server:
```bash
php artisan serve
```
Run the frontend server (in a separate terminal):
```bash
npm run dev
```
