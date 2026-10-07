import { useState, useEffect } from 'react';
import { getCurrentUserRole, hasPermission as checkPermission } from '@/services/roleService';

export const useRole = () => {
    const [role, setRole] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const fetchRole = async () => {
            setLoading(true);
            const userRole = await getCurrentUserRole();
            setRole(userRole);
            setLoading(false);
        };
        fetchRole();
    }, []);

    return { role, loading };
};

export const usePermission = (requiredPermission) => {
    const [hasAccess, setHasAccess] = useState(false);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const verifyPermission = async () => {
            if (!requiredPermission) {
                setHasAccess(true);
                setLoading(false);
                return;
            }
            setLoading(true);
            const allowed = await checkPermission(requiredPermission);
            setHasAccess(allowed);
            setLoading(false);
        };

        verifyPermission();
    }, [requiredPermission]);

    return { hasAccess, loading };
};