import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Settings from 'App/Models/Settings'
import SettingsUpdateValidator from 'App/Validators/SettingsUpdateValidator'

export default class SettingsController {
  public async get({ auth }: HttpContextContract) {
    const user = auth.use('api').user!

    await user.load('settings')

    return {
      data: {
        settings: user.settings.serialized,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const { id: userId } = auth.use('api').user!
    const attributes = await request.validate(SettingsUpdateValidator)

    const settings = await Settings.findByOrFail('user_id', userId)
    const updatedSettings = await settings.merge(attributes).save()

    return {
      data: {
        settings: updatedSettings.serialized,
      },
    }
  }
}
