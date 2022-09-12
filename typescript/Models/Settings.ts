import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import User from './User'

export default class Settings extends BaseModel {
  public static override table = 'settings'

  @column({ isPrimary: true })
  public id: string

  @column()
  public timezone: string = 'UTC'

  @column()
  public language: string = 'en'

  @column()
  public country: string = 'US'

  @column()
  public mailtagDefaultEnabled: boolean = true

  @column()
  public desktopNotifications: boolean = false

  @column()
  public emailNotifications: boolean = false

  @column()
  public linkTracking: boolean = false

  @column()
  public attachmentTracking: boolean = false

  @column()
  public customSignatures: boolean = false

  @column()
  public customSignatureForPings: boolean = false

  @column()
  public bcc: string | null = null

  @column()
  public subDomain: string | null = null

  @column()
  public boomerang: string | null = null

  @column()
  public userId: string

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  public get serialized() {
    const {
      id,
      timezone,
      language,
      country,
      mailtagDefaultEnabled,
      desktopNotifications,
      emailNotifications,
      linkTracking,
      attachmentTracking,
      customSignatures,
      customSignatureForPings,
      bcc,
      subDomain,
      boomerang,
    } = this

    return {
      id,
      timezone,
      language,
      country,
      mailtagDefaultEnabled,
      desktopNotifications,
      emailNotifications,
      linkTracking,
      attachmentTracking,
      customSignatures,
      customSignatureForPings,
      bcc,
      subDomain,
      boomerang,
    }
  }
}
