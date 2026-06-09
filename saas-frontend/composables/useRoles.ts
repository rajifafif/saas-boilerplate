import { ref } from 'vue';
import { useApi } from './useApi';

export const useRoles = () => {
  const roles = ref<any[]>([]);
  const loadingRoles = ref(false);
  const permissions = ref<any[]>([]);
  const loadingPermissions = ref(false);

  // Helper to construct URL with query params
  const createUrl = (url: string, params: any = {}) => {
      const query = new URLSearchParams();
      Object.keys(params).forEach(key => {
          if (params[key] !== undefined && params[key] !== null && params[key] !== '') {
              query.append(key, params[key]);
          }
      });
      const queryString = query.toString();
      return queryString ? `${url}?${queryString}` : url;
  };

  // Fetch Roles List
  const fetchRoles = async (options: any) => {
    loadingRoles.value = true;
    try {
      const url = createUrl('/organizations/roles', options);
      console.log('Fetching roles from:', url);
      
      const { data, response, error } = await useApi(url).json();

      if (error.value) {
          console.error("API Error fetching roles:", error.value);
      }
      
      if (data.value) {
          roles.value = data.value.data || [];
          console.log('Roles fetched:', roles.value);
      }
      
      return { data: data.value, response, error };
    } catch (error) {
      console.error("Exception fetching roles:", error);
      return { data: null, response: null, error };
    } finally {
      loadingRoles.value = false;
    }
  };

  // Fetch Single Role with Permissions
  const fetchRole = async (id: string) => {
    try {
      const { data, response, error } = await useApi(`/organizations/roles/${id}`).json();
      return { data: data.value, response, error };
    } catch (error) {
       console.error("Error fetching role:", error);
       return { data: null, response: null, error };
    }
  }

  // Fetch All Available Permissions
  const fetchPermissions = async (options: any = {}) => {
      loadingPermissions.value = true;
      try {
          const url = createUrl('/organizations/permissions', options);
          const { data, response, error } = await useApi(url).json();
          permissions.value = data.value || [];
          return { data: data.value, response, error };
      } catch (error) {
          console.error("Error fetching permissions:", error);
          return { data: null, response: null, error };
      } finally {
          loadingPermissions.value = false;
      }
  }

  const storeRole = async (roleData: any) => {
    return await useApi('/organizations/roles').post(roleData).json();
  }

  const updateRole = async (id: string, roleData: any) => {
    return await useApi(`/organizations/roles/${id}`).put(roleData).json();
  }

  const deleteRole = async (id: string) => {
    return await useApi(`/organizations/roles/${id}`).delete().json();
  }

  return { 
      roles, 
      loadingRoles, 
      permissions, 
      loadingPermissions, 
      fetchRoles, 
      fetchRole, 
      fetchPermissions, 
      storeRole, 
      updateRole, 
      deleteRole 
  };
};
