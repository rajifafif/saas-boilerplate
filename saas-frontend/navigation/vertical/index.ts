// import appsAndPages from './apps-and-pages'
// import charts from './charts'
import dashboard from './dashboard'
import master from './master'
// import forms from './forms'
// import others from './others'
// import uiElements from './ui-elements'
import type { VerticalNavItems } from '@layouts/types'

export default [...dashboard, ...master] as VerticalNavItems
// export default [...dashboard, ...appsAndPages, ...uiElements, ...forms, ...charts, ...others] as VerticalNavItems
