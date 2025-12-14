import { useEffect, useState } from 'react';
import { Bell } from 'lucide-react';
import { notificationAPI } from '../utils/api';

const NotificationBell = () => {
  const [unread, setUnread] = useState(0);
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);

  const refreshCount = async () => {
    try {
      const { count } = await notificationAPI.unreadCount();
      setUnread(count);
    } catch {}
  };

  const loadItems = async () => {
    setLoading(true);
    try {
      const list = await notificationAPI.list();
      setItems(list);
    } catch {}
    setLoading(false);
  };

  useEffect(() => {
    refreshCount();
    const id = setInterval(refreshCount, 30000);
    return () => clearInterval(id);
  }, []);

  const toggleOpen = async () => {
    const next = !open;
    setOpen(next);
    if (next) {
      await loadItems();
    }
  };

  const markAll = async () => {
    await notificationAPI.markAllRead();
    setUnread(0);
    setItems(items.map((i) => ({ ...i, read: true })));
  };

  return (
    <div className="relative">
      <button onClick={toggleOpen} className="relative text-gray-700 dark:text-gray-300 hover:text-blue-600">
        <Bell className="w-5 h-5" />
        {unread > 0 && (
          <span className="absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full px-1">
            {unread}
          </span>
        )}
      </button>
      {open && (
        <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg z-50">
          <div className="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-700">
            <span className="font-semibold text-gray-800 dark:text-gray-200">Notifications</span>
            <button onClick={markAll} className="text-sm text-blue-600 hover:underline dark:text-blue-400">
              Mark all as read
            </button>
          </div>
          <div className="max-h-96 overflow-auto">
            {loading ? (
              <div className="p-4 text-sm text-gray-500 dark:text-gray-400">Loading...</div>
            ) : items.length === 0 ? (
              <div className="p-4 text-sm text-gray-500 dark:text-gray-400">No notifications</div>
            ) : (
              items.map((n) => (
                <div
                  key={n.id}
                  className={`px-4 py-3 text-sm ${n.read ? 'bg-white dark:bg-gray-700' : 'bg-blue-50 dark:bg-blue-600'}`}
                >
                  <div className="text-gray-800 dark:text-gray-200">{n.message}</div>
                  <div className="text-gray-400 text-xs dark:text-gray-500">{new Date(n.createdAt).toLocaleString()}</div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
};

export default NotificationBell;